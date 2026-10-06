<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration',
        'entity_type',
        'entity_id',
        'external_id',
        'external_sku',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeFor(string $integration, string $entityType)
    {
        return $this->where('integration', $integration)
            ->where('entity_type', $entityType);
    }
}
