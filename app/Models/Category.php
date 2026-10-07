<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id','name','slug','description','meta_title','meta_description','is_active','sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean','sort_order' => 'integer'];
    }

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name'); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function media(): MorphMany { return $this->morphMany(Media::class, 'mediable'); }

    public function coverMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('collection', 'cover')
            ->orderBy('sort_order');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
