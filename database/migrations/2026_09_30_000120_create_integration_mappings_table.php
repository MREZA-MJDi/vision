<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_mappings', function (Blueprint $table) {
            $table->id();

            $table->string('integration', 50);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');

            $table->string('external_id', 191);
            $table->string('external_sku', 191)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['integration', 'entity_type', 'external_id'],
                'integration_entity_external_unique'
            );

            $table->unique(
                ['integration', 'entity_type', 'external_sku'],
                'integration_entity_sku_unique'
            );

            $table->index(
                ['entity_type', 'entity_id'],
                'integration_entity_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_mappings');
    }
};
