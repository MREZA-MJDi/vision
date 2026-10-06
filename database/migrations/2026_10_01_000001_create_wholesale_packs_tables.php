<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wholesale_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('pack_quantity')->default(0);
            $table->decimal('pack_price', 15, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wholesale_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wholesale_pack_id')->constrained('wholesale_packs')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['wholesale_pack_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_pack_items');
        Schema::dropIfExists('wholesale_packs');
    }
};
