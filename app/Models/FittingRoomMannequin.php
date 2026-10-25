<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FittingRoomMannequin extends Model
{
    protected $fillable = [
        'code', 'label', 'audience', 'pose_key', 'canvas_width', 'canvas_height',
        'base_media_id', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'canvas_width' => 'integer',
            'canvas_height' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function baseMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'base_media_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function looks(): HasMany
    {
        return $this->hasMany(FittingRoomLook::class, 'mannequin_id')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
