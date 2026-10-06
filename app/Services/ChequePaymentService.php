<?php

namespace App\Services;

use App\Models\ChequePermission;
use App\Models\ChequePayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ChequePaymentService
{
    public function __construct(
        private readonly WholesaleEligibilityService $eligibility,
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
    ) {
    }

    public function submit(
        Order $order,
        User $user,
        array $data
    ): Payment {
        $this->eligibility->assertChequeAllowed(
            $user,
            (float) $order->total
        );

        abort_unless(
            $order->user_id === $user->id
                && $order->order_type === 'wholesale'
                && $order->payment_status === 'pending'
                && $order->status === 'pending',
            422,
            'این سفارش شرایط ثبت پرداخت چکی را ندارد.'
        );

        /** @var UploadedFile|null $file */
        $file = $data['cheque_image'] ?? null;
        $storedPath = null;

        try {
            if ($file instanceof UploadedFile) {
                $storedPath = Storage::disk('local')->putFile('private/cheques', $file);

                abort_if(
                    ! $storedPath,
                    500,
                    'ذخیره تصویر چک انجام نشد.'
                );
            }

            return DB::transaction(function () use (
                $order,
                $user,
                $data,
                $storedPath
            ): Payment {
                $lockedOrder = Order::query()
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                $permission = ChequePermission::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $permission?->allows((float) $lockedOrder->total),
                    403,
                    'دسترسی پرداخت چکی این حساب در حال حاضر مجاز نیست.'
                );

                abort_unless(
                    $lockedOrder->user_id === $user->id
                    && $lockedOrder->order_type === 'wholesale'
                    && $lockedOrder->payment_status === 'pending'
                    && $lockedOrder->status === 'pending',
                    422,
                    'این سفارش دیگر قابل ثبت پرداخت چکی نیست.'
                );

                abort_if(
                    $lockedOrder->chequePayment()->exists(),
                    409,
                    'برای این سفارش قبلاً اطلاعات چک ثبت شده است.'
                );

                $payment = $lockedOrder->payments()
                    ->where('gateway', 'cheque')
                    ->lockForUpdate()
                    ->first();

                abort_if(
                    $payment?->status === 'paid',
                    409,
                    'این سفارش قبلاً پرداخت شده است.'
                );

                $payment ??= $lockedOrder->payments()->create([
                    'gateway' => 'cheque',
                    'idempotency_key' => 'cheque:order:' . $lockedOrder->id,
                    'amount' => $lockedOrder->total,
                    'status' => 'pending',
                ]);

                $cheque = $lockedOrder->chequePayment()->create([
                    'payment_id' => $payment->id,
                    'sayad_id' => trim((string) $data['sayad_id']),
                    'cheque_number' => $this->nullableString(
                        $data['cheque_number'] ?? null
                    ),
                    'bank_name' => trim((string) $data['bank_name']),
                    'account_holder' => $this->nullableString(
                        $data['account_holder'] ?? null
                    ),
                    'amount' => $lockedOrder->total,
                    'due_date' => $data['due_date'],
                    'image_path' => $storedPath,
                    'status' => 'submitted',
                ]);

                $payment->update([
                    'metadata' => [
                        'cheque_payment_id' => $cheque->id,
                    ],
                ]);

                return $payment->fresh();
            });
        } catch (Throwable $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }
    }

    public function moveToReview(ChequePayment $cheque, User $admin): ChequePayment
    {
        return $this->transition($cheque, $admin, 'under_review');
    }

    public function accept(
        ChequePayment $cheque,
        User $admin,
        ?string $note = null
    ): ChequePayment {
        $this->assertAdmin($admin);

        return DB::transaction(function () use (
            $cheque,
            $admin,
            $note
        ): ChequePayment {
            $cheque = $this->lockChequeFlow($cheque);

            if ($cheque->status === 'accepted') {
                return $cheque;
            }

            abort_unless(
                $cheque->canTransitionTo('accepted'),
                422,
                'وضعیت فعلی چک قابل تأیید نیست.'
            );

            abort_unless(
                $cheque->order
                && ! in_array($cheque->order->status, Order::CANCEL_LIKE_STATUSES, true)
                && in_array($cheque->order->status, ['pending', 'confirmed'], true)
                && $cheque->payment->status === 'pending',
                422,
                'سفارش یا پرداخت چک دیگر قابل تأیید نیست.'
            );

            $cheque->update([
                'status' => 'accepted',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $cheque->order()->update([
                'status' => 'confirmed',
            ]);

            return $cheque->fresh(['order', 'payment']);
        });

        return $result;
    }

    public function reject(
        ChequePayment $cheque,
        User $admin,
        ?string $note = null
    ): ChequePayment {
        $this->assertAdmin($admin);

        $result = DB::transaction(function () use (
            $cheque,
            $admin,
            $note
        ): ChequePayment {
            $cheque = $this->lockChequeFlow($cheque);

            if ($cheque->status === 'rejected') {
                return $cheque;
            }

            abort_unless(
                $cheque->canTransitionTo('rejected'),
                422,
                'وضعیت فعلی چک قابل رد کردن نیست.'
            );

            $cheque->update([
                'status' => 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            if ($cheque->order->payment_status !== 'paid') {
                if (! in_array($cheque->order->status, Order::CANCEL_LIKE_STATUSES, true)) {
                $this->orders->updateStatus(
                    $cheque->order,
                    'cancelled',
                    'failed',
                    $note ?: 'پرداخت چکی توسط مدیریت رد شد.'
                );
                } else {
                    $this->payments->markFailed($cheque->order, 'cheque');
                }
            }

            return $cheque->fresh(['order', 'payment']);
        });

        return $result;
    }

    public function markDeposited(ChequePayment $cheque, User $admin): ChequePayment
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($cheque): ChequePayment {
            $cheque = $this->lockChequeFlow($cheque);

            if ($cheque->status === 'deposited') {
                return $cheque;
            }

            abort_unless(
                $cheque->canTransitionTo('deposited'),
                422,
                'این چک هنوز قابل ثبت به عنوان واریزی نیست.'
            );

            abort_unless(
                ! in_array($cheque->order->status, Order::CANCEL_LIKE_STATUSES, true)
                    && $cheque->payment->status === 'pending',
                422,
                'سفارش یا پرداخت چک دیگر قابل واریز نیست.'
            );

            $cheque->update([
                'status' => 'deposited',
                'deposited_at' => now(),
            ]);

            return $cheque->fresh(['order', 'payment']);
        });
    }

    public function markCleared(ChequePayment $cheque, User $admin): ChequePayment
    {
        $this->assertAdmin($admin);

        return DB::transaction(function () use ($cheque): ChequePayment {
            $cheque = $this->lockChequeFlow($cheque);

            if ($cheque->status === 'cleared') {
                return $cheque;
            }

            abort_unless(
                $cheque->canTransitionTo('cleared'),
                422,
                'این چک هنوز قابل تسویه نیست.'
            );

            abort_unless(
                ! in_array($cheque->order->status, Order::CANCEL_LIKE_STATUSES, true)
                    && $cheque->order->payment_status === 'pending'
                    && $cheque->payment->status === 'pending',
                409,
                'سفارش یا پرداخت چک دیگر قابل تسویه نیست.'
            );

            $this->payments->markPaid(
                $cheque->order,
                'cheque',
                $cheque->sayad_id
            );

            $cheque->update([
                'status' => 'cleared',
                'cleared_at' => now(),
            ]);

            return $cheque->fresh(['order', 'payment']);
        });
    }

    public function markBounced(
        ChequePayment $cheque,
        User $admin,
        ?string $note = null
    ): ChequePayment {
        $this->assertAdmin($admin);

        return DB::transaction(function () use (
            $cheque,
            $note
        ): ChequePayment {
            $cheque = $this->lockChequeFlow($cheque);

            if ($cheque->status === 'bounced') {
                return $cheque;
            }

            abort_unless(
                $cheque->canTransitionTo('bounced'),
                422,
                'این چک هنوز قابل ثبت به عنوان برگشتی نیست.'
            );

            abort_unless(
                $cheque->order->payment_status !== 'paid',
                409,
                'برای سفارش تسویه‌شده امکان ثبت برگشت چک وجود ندارد.'
            );

            $cheque->update([
                'status' => 'bounced',
                'bounced_at' => now(),
                'review_note' => $note ?: $cheque->review_note,
            ]);

            if (! in_array($cheque->order->status, Order::CANCEL_LIKE_STATUSES, true)) {
                $this->orders->updateStatus(
                    $cheque->order,
                    'cancelled',
                    'failed',
                    $note ?: 'چک برگشت خورد و سفارش لغو شد.'
                );
            } else {
                $this->payments->markFailed($cheque->order, 'cheque');
            }

            return $cheque->fresh(['order', 'payment']);
        });
    }

    private function transition(
        ChequePayment $cheque,
        User $admin,
        string $next
    ): ChequePayment {
        $this->assertAdmin($admin);

        return DB::transaction(function () use (
            $cheque,
            $next
        ): ChequePayment {
            $cheque = $this->lockChequeFlow($cheque);

            abort_unless(
                $cheque->canTransitionTo($next),
                422,
                'تغییر وضعیت چک مجاز نیست.'
            );

            if ($cheque->status !== $next) {
                $cheque->update(['status' => $next]);
            }

            return $cheque->fresh(['order', 'payment']);
        });
    }

    private function assertAdmin(User $admin): void
    {
        abort_unless(
            $admin->exists && $admin->isAdmin(),
            403,
            'فقط مدیر فروشگاه مجاز به مدیریت چک است.'
        );
    }

    private function lockChequeFlow(ChequePayment $cheque): ChequePayment
    {
        // Match the order -> payment -> cheque lock order used by payment callbacks.
        Order::query()->lockForUpdate()->findOrFail($cheque->order_id);
        Payment::query()->lockForUpdate()->findOrFail($cheque->payment_id);

        return ChequePayment::query()
            ->lockForUpdate()
            ->with(['order', 'payment'])
            ->findOrFail($cheque->id);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
