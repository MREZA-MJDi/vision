<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id','sku','size','color','color_code','price','sale_price','stock',
        'low_stock_threshold','is_active','sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function media(): MorphMany { return $this->morphMany(Media::class, 'mediable'); }

    public function galleryMedia(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->where('collection', 'gallery')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Keep card eager loading bounded to one real gallery asset per variant.
     * Without ofMany(), eager loading this relation can hydrate every matching
     * gallery row even though storefront cards only consume the first image.
     */
    public function primaryGalleryMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->ofMany(
            ['sort_order' => 'min', 'id' => 'min'],
            fn ($query) => $query
                ->where('collection', 'gallery')
                ->whereNotNull('path')
                ->whereRaw("TRIM(path) <> ''")
        );
    }

    public function cartItems(): HasMany { return $this->hasMany(CartItem::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
    public function inventoryMovements(): HasMany { return $this->hasMany(InventoryMovement::class); }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price < (float) $this->price;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_values(array_filter([
            $this->size,
            $this->color,
        ]));

        return $parts ? implode(' / ', $parts) : ($this->sku ?: $this->product?->name ?: 'Variant');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeInStock($query) { return $query->where('stock', '>', 0); }
}
