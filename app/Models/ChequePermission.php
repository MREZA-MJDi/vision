<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enabled',
        'max_order_amount',
        'approved_by',
        'approved_at',
        'requested_at',
        'requested_amount',
        'disabled_by',
        'disabled_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'max_order_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'requested_at' => 'datetime',
            'requested_amount' => 'decimal:2',
            'disabled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by');
    }

    public function allows(float $amount): bool
    {
        return $this->isApproved()
            && (
                $this->max_order_amount === null
                || $amount <= (float) $this->max_order_amount
            );
    }

    public function isApproved(): bool
    {
        return $this->enabled && $this->approved_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->enabled
            && $this->requested_at !== null
            && $this->disabled_at === null;
    }
}
