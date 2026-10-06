<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('short_description')
                ->nullable();

            $table->longText('description')
                ->nullable();

            $table->json('attributes')
                ->nullable();

            $table->string('meta_title')
                ->nullable();

            $table->text('meta_description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->boolean('is_featured')
                ->default(false)
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
            $table->index(['brand_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
