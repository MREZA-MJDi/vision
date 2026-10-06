<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;

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

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $this->path)) {
            return $this->path;
        }

        return route('store.media', ['path' => ltrim($this->path, '/')]);
    }
}
