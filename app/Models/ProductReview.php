<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'product_id', 'user_id', 'reviewer_name', 'reviewer_email',
        'rating', 'title', 'body', 'status', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
