<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name','slug','description','meta_title','meta_description','is_active',
    ];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function media(): MorphMany { return $this->morphMany(Media::class, 'mediable'); }

    public function logoMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('collection', 'logo')
            ->orderBy('sort_order');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
