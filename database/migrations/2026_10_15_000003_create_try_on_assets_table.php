<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('try_on_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('try_on_mannequin_id')->constrained('try_on_mannequins')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('slot', 24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['try_on_mannequin_id', 'slot', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('try_on_assets');
    }
};
