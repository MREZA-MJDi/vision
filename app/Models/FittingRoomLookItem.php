<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FittingRoomLookItem extends Model
{
    public const SLOTS = ['top', 'bottom'];

    protected $fillable = ['look_id', 'product_variant_id', 'slot', 'fit_y', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['fit_y' => 'float', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function look(): BelongsTo
    {
        return $this->belongsTo(FittingRoomLook::class, 'look_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
