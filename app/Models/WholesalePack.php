<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WholesalePack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'pack_quantity',
        'pack_price',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pack_quantity' => 'integer',
            'pack_price' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(WholesalePackItem::class)->orderBy('id');
    }

    public function getDisplayPriceAttribute(): float
    {
        return (float) $this->pack_price;
    }
}
