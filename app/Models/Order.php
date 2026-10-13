<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending',
        'confirmed',
        'preparing',
        'shipped',
        'out_for_delivery',
        'delivered',
        'cancelled',
        'returned',
    ];

    /**
     * Explicit order-state machine shared by admin actions and the UI.
     *
     * Terminal states cannot be reopened, which also prevents a second stock
     * restoration from being triggered by a repeated status update.
     */
    public const STATUS_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['out_for_delivery', 'returned'],
        'out_for_delivery' => ['delivered', 'returned'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'returned' => [],
    ];

    public const PAYMENT_STATUSES = [
        'pending',
        'paid',
        'failed',
        'refunded',
    ];

    public const CANCEL_LIKE_STATUSES = [
        'cancelled',
        'returned',
    ];

    protected $fillable = [
        'user_id',
        'address_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'shipping_province',
        'shipping_city',
        'postal_code',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'discount',
        'shipping_cost',
        'tax',
        'total',
        'customer_note',
        'tracking_code',
        'placed_at',
        'paid_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActiveProcessing($query)
    {
        return $query->whereIn('status', [
            'pending',
            'confirmed',
            'preparing',
        ]);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function isCancelledLike(): bool
    {
        return in_array(
            $this->status,
            self::CANCEL_LIKE_STATUSES,
            true
        );
    }

    /**
     * Return valid next states for the current order state.
     */
    public function allowedNextStatuses(): array
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? [];
    }

    /**
     * A same-state update is treated as an idempotent no-op.
     */
    public function canTransitionTo(string $nextStatus): bool
    {
        return $nextStatus === $this->status
            || in_array($nextStatus, $this->allowedNextStatuses(), true);
    }
}
