<?php

namespace Rm\VirtualTryOn\Models;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TryOnMannequin extends Model
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

    public function assets(): HasMany
    {
        return $this->hasMany(TryOnAsset::class, 'try_on_mannequin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
