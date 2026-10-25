<?php

namespace Rm\VirtualTryOn\Models;

use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TryOnAsset extends Model
{
    protected $fillable = [
        'product_variant_id', 'try_on_mannequin_id', 'media_id', 'slot', 'is_active', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_default' => 'boolean'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(config('virtual-try-on.models.variant', ProductVariant::class), 'product_variant_id');
    }

    public function mannequin(): BelongsTo
    {
        return $this->belongsTo(config('virtual-try-on.models.mannequin', TryOnMannequin::class), 'try_on_mannequin_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(config('virtual-try-on.models.media', Media::class));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
