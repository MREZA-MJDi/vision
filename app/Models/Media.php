<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    public const DEFAULT_CARD_FRAME = ['scale' => 100, 'x' => 0, 'y' => 0];

    protected $fillable = [
        'mediable_type','mediable_id','collection','disk','path','original_name','mime_type','size',
        'width','height','alt_text','sort_order','metadata','uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function mediable(): MorphTo { return $this->morphTo(); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function scopeCollection($query, string $collection) { return $query->where('collection', $collection); }

    public function cardFrame(): array
    {
        $frame = $this->metadata['card_frame'] ?? [];
        if (! is_array($frame)) {
            return self::DEFAULT_CARD_FRAME;
        }

        $coordinate = static function (string $key, int $min, int $max) use ($frame): int {
            $value = $frame[$key] ?? null;

            return is_numeric($value) ? min($max, max($min, (int) $value)) : self::DEFAULT_CARD_FRAME[$key];
        };

        return [
            'scale' => $coordinate('scale', 80, 125),
            'x' => $coordinate('x', -12, 12),
            'y' => $coordinate('y', -12, 12),
        ];
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if (preg_match('/^https?:\\/\\//i', $this->path)) {
            return $this->path;
        }

        $disk = $this->disk ?: 'public';
        $filesystem = Storage::disk($disk);

        // Avoid emitting broken URLs for stale local media rows. Do not issue
        // a filesystem/network existence check for remote disks.
        if (config("filesystems.disks.{$disk}.driver") === 'local'
            && ! $filesystem->exists($this->path)) {
            return null;
        }

        return $filesystem->url($this->path);
    }
}
