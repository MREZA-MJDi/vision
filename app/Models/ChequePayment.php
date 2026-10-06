<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequePayment extends Model
{
    use HasFactory;

    public const STATUSES = [
        'submitted',
        'under_review',
        'accepted',
        'deposited',
        'cleared',
        'rejected',
        'bounced',
        'cancelled',
    ];

    protected $fillable = [
        'payment_id',
        'order_id',
        'sayad_id',
        'cheque_number',
        'bank_name',
        'account_holder',
        'amount',
        'due_date',
        'image_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'deposited_at',
        'cleared_at',
        'bounced_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'reviewed_at' => 'datetime',
            'deposited_at' => 'datetime',
            'cleared_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function canTransitionTo(string $next): bool
    {
        if ($next === $this->status) {
            return true;
        }

        $allowed = match ($this->status) {
            'submitted' => ['under_review', 'rejected', 'cancelled'],
            'under_review' => ['accepted', 'rejected', 'cancelled'],
            'accepted' => ['deposited', 'cancelled'],
            'deposited' => ['cleared', 'bounced'],
            'cleared', 'rejected', 'bounced', 'cancelled' => [],
            default => [],
        };

        return in_array($next, $allowed, true);
    }
}
