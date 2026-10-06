<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            // Snapshot
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('product_image')->nullable();

            $table->decimal('unit_price', 14, 2);

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('line_total', 14, 2);

            $table->timestamps();

            $table->index([
                'order_id',
                'product_variant_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
