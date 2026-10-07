<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

final class PaymentService
{
    public function __construct(
        private readonly DoubleEntryAccountingService $accounting,
    ) {
    }

    public function matchStatus(Order $order, string $status): void
    {
        match ($status) {
            'paid' => $this->markPaid($order),
            'failed' => $this->markFailed($order),
            'refunded' => $this->refund($order),
            'pending' => $this->setStatus($order, 'pending'),
            default => abort(422, 'وضعیت پرداخت معتبر نیست.'),
        };
    }

    public function setStatus(
        Order $order,
        string $status,
        ?string $gateway = null
    ): Payment {
        abort_unless(
            in_array($status, Payment::STATUSES, true),
            422,
            'وضعیت پرداخت معتبر نیست.'
        );

        return DB::transaction(function () use (
            $order,
            $status,
            $gateway
        ): Payment {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $payment = $order->payments()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $resolvedGateway = $gateway
                ?: $payment?->gateway
                ?: 'manual';

            $payment ??= $order->payments()->create([
                'gateway' => $resolvedGateway,
                'idempotency_key' => $resolvedGateway
                    . ':order:'
                    . $order->id,
                'amount' => $order->total,
                'status' => 'pending',
            ]);

            abort_if(
                in_array($payment->status, ['paid', 'refunded'], true)
                && $status !== $payment->status,
                409,
                $payment->status === 'refunded'
                    ? 'پرداخت بازپرداخت‌شده قابل تغییر نیست.'
                    : 'پرداخت موفق قابل بازگردانی به وضعیت قبلی نیست.'
            );

            $payment->update([
                'gateway' => $resolvedGateway,
                'amount' => $order->total,
                'status' => $status,
                'paid_at' => $status === 'paid'
                    ? ($payment->paid_at ?? now())
                    : $payment->paid_at,
            ]);

            $order->update([
                'payment_status' => $status,
                'paid_at' => $status === 'paid'
                    ? ($order->paid_at ?? now())
                    : $order->paid_at,
                'payment_method' => $resolvedGateway,
            ]);

            return $payment->fresh();
        });
    }

    public function markPaid(
        Order $order,
        ?string $gateway = null,
        ?string $reference = null
    ): Payment {
        return DB::transaction(function () use (
            $order,
            $gateway,
            $reference
        ): Payment {
            $payment = $this->setStatus(
                $order,
                'paid',
                $gateway
            );

            $payment->update([
                'reference_number' =>
                    $reference ?? $payment->reference_number,
            ]);

            $this->accounting->recordSale(
                reference: $order,
                amount: (float) $order->total,
                settlementAccount: $this->settlementAccountForGateway($gateway ?? $payment->gateway),
                sourceKey: 'sale:order:' . $order->id,
                description: "فروش سفارش {$order->order_number}",
            );

            FinancialTransaction::firstOrCreate([
                'type' => 'income',
                'category' => 'order',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
            ], [
                'amount' => $order->total,
                'description' => "دریافت سفارش {$order->order_number}",
                'transaction_date' =>
                    optional($order->placed_at)->toDateString()
                    ?: now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            return $payment->fresh();
        });
    }

    public function markFailed(
        Order $order,
        ?string $gateway = null
    ): Payment {
        return $this->setStatus(
            $order,
            'failed',
            $gateway
        );
    }

    public function refund(Order $order): Payment
    {
        return DB::transaction(function () use ($order): Payment {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $payment = $order->payments()
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $payment->status === 'paid'
                && $order->payment_status === 'paid',
                422,
                'فقط یک پرداخت موفق قابل بازپرداخت است.'
            );

            $payment->update([
                'status' => 'refunded',
            ]);

            $order->update([
                'payment_status' => 'refunded',
            ]);

            $this->accounting->recordRefund(
                reference: $order,
                amount: (float) $order->total,
                settlementAccount: $this->settlementAccountForGateway($payment->gateway),
                sourceKey: 'refund:order:' . $order->id,
                description: "بازپرداخت سفارش {$order->order_number}",
            );

            FinancialTransaction::firstOrCreate([
                'type' => 'expense',
                'category' => 'refund',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
            ], [
                'amount' => $order->total,
                'description' => "بازپرداخت سفارش {$order->order_number}",
                'transaction_date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            return $payment->fresh();
        });
    }
    private function settlementAccountForGateway(?string $gateway): string
    {
        return match ($gateway) {
            'cash', 'manual' => 'cash',
            default => 'bank',
        };
    }
}
