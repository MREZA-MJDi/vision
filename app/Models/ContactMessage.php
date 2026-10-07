<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_REPLIED = 'replied';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'read_at',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function scopeUnread($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function markAsRead(): void
    {
        $this->forceFill([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ])->save();
    }

    public function markAsReplied(): void
    {
        $this->forceFill([
            'status' => self::STATUS_REPLIED,
            'replied_at' => now(),
        ])->save();
    }
}
