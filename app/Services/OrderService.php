<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function updateStatus(
        Order $order,
        string $newStatus,
        string $paymentStatus,
        ?string $customerNote = null,
        ?string $trackingCode = null
    ): Order {
        abort_unless(
            in_array($newStatus, Order::STATUSES, true),
            422,
            'وضعیت سفارش نامعتبر است.'
        );

        abort_unless(
            in_array($paymentStatus, Order::PAYMENT_STATUSES, true),
            422,
            'وضعیت پرداخت نامعتبر است.'
        );

        return DB::transaction(function () use (
            $order,
            $newStatus,
            $paymentStatus,
            $customerNote,
            $trackingCode
        ): Order {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $oldStatus = $order->status;
            $oldPaymentStatus = $order->payment_status;

            $this->assertStatusTransition(
                $oldStatus,
                $newStatus
            );

            abort_if(
                $newStatus === 'returned'
                    && $oldPaymentStatus === 'paid'
                    && $paymentStatus !== 'refunded',
                422,
                'برای ثبت مرجوعی سفارش پرداخت‌شده، بازپرداخت را هم‌زمان ثبت کنید.'
            );

            abort_if(
                $paymentStatus === 'refunded'
                    && ($newStatus !== 'returned' || $oldPaymentStatus !== 'paid'),
                422,
                'بازپرداخت فقط همراه با مرجوعی سفارش پرداخت‌شده مجاز است.'
            );

            $wasCancelledLike = in_array(
                $oldStatus,
                Order::CANCEL_LIKE_STATUSES,
                true
            );

            $willBeCancelledLike = in_array(
                $newStatus,
                Order::CANCEL_LIKE_STATUSES,
                true
            );

            if (! $wasCancelledLike && $willBeCancelledLike) {
                $this->restoreInventory($order);
            }

            if ($wasCancelledLike && ! $willBeCancelledLike) {
                $this->deductInventoryAgain($order);
            }

            $order->update([
                'status' => $newStatus,
                'customer_note' => $customerNote,
                'tracking_code' => $trackingCode,
                'shipped_at' => $this->statusTimestamp(
                    $newStatus,
                    'shipped_at',
                    $order->shipped_at
                ),
                'delivered_at' => $this->statusTimestamp(
                    $newStatus,
                    'delivered_at',
                    $order->delivered_at
                ),
                'cancelled_at' => $willBeCancelledLike
                    ? ($order->cancelled_at ?? now())
                    : $order->cancelled_at,
            ]);

            if ($paymentStatus !== $oldPaymentStatus) {
                abort_if(
                    $paymentStatus === 'paid',
                    422,
                    'ثبت پرداخت موفق فقط از مسیر تأیید درگاه یا تسویه ابزار پرداخت انجام می‌شود.'
                );

                abort_if(
                    $paymentStatus === 'refunded',
                    422,
                    'بازپرداخت باید از مسیر امن پرداخت انجام شود.'
                );

                $this->payment->matchStatus(
                    $order,
                    $paymentStatus
                );
            }

            return $order->fresh([
                'items',
                'payments',
            ]);
        }, 3);
    }

    private function restoreInventory(Order $order): void
    {
        $order->load('items');

        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = ProductVariant::query()
                ->lockForUpdate()
                ->find($item->product_variant_id);

            if (! $variant) {
                continue;
            }

            $variant->increment(
                'stock',
                (int) $item->quantity
            );

            $variant->refresh();

            $variant->inventoryMovements()->create([
                'type' => 'return',
                'quantity' => (int) $item->quantity,
                'stock_after' => $variant->stock,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => "بازگشت موجودی سفارش {$order->order_number}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function deductInventoryAgain(Order $order): void
    {
        $order->load('items');

        foreach ($order->items as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            $variant = ProductVariant::query()
                ->lockForUpdate()
                ->find($item->product_variant_id);

            abort_unless(
                $variant
                && $variant->stock >= $item->quantity,
                422,
                "موجودی برای فعال‌سازی مجدد سفارش «{$order->order_number}» کافی نیست."
            );

            $variant->decrement(
                'stock',
                (int) $item->quantity
            );

            $variant->refresh();

            $variant->inventoryMovements()->create([
                'type' => 'sale',
                'quantity' => -((int) $item->quantity),
                'stock_after' => $variant->stock,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => "کسر مجدد موجودی سفارش {$order->order_number}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function resolveAddressId(
        ?int $addressId,
        ?User $user
    ): ?int {
        if (! $addressId) {
            return null;
        }

        abort_unless(
            $user
            && Address::query()
                ->whereKey($addressId)
                ->where('user_id', $user->id)
                ->exists(),
            422,
            'آدرس انتخاب‌شده معتبر نیست.'
        );

        return $addressId;
    }

    private function variantName(
        ProductVariant $variant
    ): ?string {
        $parts = array_values(array_filter([
            $variant->size
                ? "سایز {$variant->size}"
                : null,

            $variant->color
                ? "رنگ {$variant->color}"
                : null,
        ]));

        return $parts
            ? implode(' / ', $parts)
            : null;
    }

    private function assertStatusTransition(
        string $from,
        string $to
    ): void {
        if ($from === $to) {
            return;
        }

        $allowed = match ($from) {
            'pending' => [
                'confirmed',
                'cancelled',
            ],

            'confirmed' => [
                'preparing',
                'cancelled',
            ],

            'preparing' => [
                'shipped',
                'cancelled',
            ],

            'shipped' => [
                'delivered',
                'returned',
            ],

            'delivered' => [
                'returned',
            ],

            'cancelled',
            'returned' => [],

            default => [],
        };

        abort_unless(
            in_array($to, $allowed, true),
            422,
            'تغییر وضعیت سفارش از وضعیت فعلی مجاز نیست.'
        );
    }

    private function statusTimestamp(
        string $status,
        string $field,
        mixed $current
    ): mixed {
        return match ($field) {
            'shipped_at' => $status === 'shipped'
                ? ($current ?? now())
                : $current,

            'delivered_at' => $status === 'delivered'
                ? ($current ?? now())
                : $current,

            default => $current,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    private function orderNumber(): string
    {
        do {
            $number = 'JN-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
        } while (
            Order::query()
                ->where('order_number', $number)
                ->exists()
        );

        return $number;
    }
}

