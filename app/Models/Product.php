<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id','brand_id','name','slug','short_description','description','attributes',
        'meta_title','meta_description','is_active','is_featured','is_hero','sort_order',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_hero' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The single active variant used by catalog cards and lightweight listings.
     *
     * Keep full variants loading for the product detail page only.
     */
    public function primaryActiveVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->ofMany(
            ['sort_order' => 'min', 'id' => 'min'],
            fn ($query) => $query->where('is_active', true)
        );
    }

    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
    public function wishlistItems(): HasMany { return $this->hasMany(WishlistItem::class); }
    public function media(): MorphMany { return $this->morphMany(Media::class, 'mediable'); }

    public function galleryMedia(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')
            ->where('collection', 'gallery')
            ->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The first gallery asset used by lightweight product listings.
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

    public function integrationMappings(): MorphMany
    {
        return $this->morphMany(IntegrationMapping::class, 'entity');
    }

    public function defaultVariant(): ?ProductVariant
    {
        return $this->relationLoaded('variants')
            ? $this->variants->first()
            : $this->variants()->first();
    }

    public function scopeOrderByEffectivePrice($query, string $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $priceSubquery = ProductVariant::query()
            ->selectRaw('COALESCE(sale_price, price)')
            ->whereColumn('product_id', 'products.id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(1);

        return $query
            ->orderBy($priceSubquery, $direction)
            ->orderBy('id');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true); }

    public function scopeLowStock($query)
    {
        return $query->whereHas('activeVariants', fn ($q) =>
            $q->whereColumn('stock', '<=', 'low_stock_threshold')
        );
    }
}
