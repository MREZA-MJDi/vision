<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();

            $table->string('size')
                ->nullable();

            $table->string('color')
                ->nullable();

            $table->string('color_code', 20)
                ->nullable();

            $table->decimal('price', 14, 2);

            $table->decimal('sale_price', 14, 2)
                ->nullable();

            $table->unsignedInteger('stock')
                ->default(0)
                ->index();

            $table->unsignedInteger('low_stock_threshold')
                ->default(5);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'product_id',
                'is_active',
                'sort_order',
            ]);

            $table->index([
                'product_id',
                'size',
                'color',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
