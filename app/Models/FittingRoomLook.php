<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FittingRoomLook extends Model
{
    protected $fillable = [
        'mannequin_id', 'code', 'label', 'preview_top_media_id', 'preview_bottom_media_id',
        'preview_combined_media_id', 'preview_suggested_media_id', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function mannequin(): BelongsTo
    {
        return $this->belongsTo(FittingRoomMannequin::class, 'mannequin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FittingRoomLookItem::class, 'look_id')->orderBy('sort_order')->orderBy('id');
    }

    public function previewTopMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_top_media_id');
    }

    public function previewBottomMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_bottom_media_id');
    }

    public function previewCombinedMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_combined_media_id');
    }

    public function previewSuggestedMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'preview_suggested_media_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
