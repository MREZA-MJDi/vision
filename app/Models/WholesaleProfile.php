<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleProfile extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending',
        'approved',
        'suspended',
        'rejected',
    ];

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'business_phone',
        'business_address',
        'status',
        'approved_by',
        'approved_at',
        'suspended_by',
        'suspended_at',
        'admin_note',
        'minimum_order_amount',
        'minimum_order_quantity',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'minimum_order_amount' => 'decimal:2',
            'minimum_order_quantity' => 'integer',
            'suspended_at' => 'datetime',
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

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
