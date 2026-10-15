<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('try_on_mannequins', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('label', 160);
            $table->string('audience', 16)->index();
            $table->string('pose_key', 80)->default('front-neutral');
            $table->unsignedSmallInteger('canvas_width')->default(1024);
            $table->unsignedSmallInteger('canvas_height')->default(1536);
            $table->foreignId('base_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['audience', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('try_on_mannequins');
    }
};
