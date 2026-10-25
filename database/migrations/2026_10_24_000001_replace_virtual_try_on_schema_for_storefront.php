<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // Retire the old transparent-layer schema; the approved storefront uses
        // pre-rendered 2:3 mannequin scenes for T / B / TB / S states.
        Schema::dropIfExists('try_on_assets');
        Schema::dropIfExists('try_on_mannequins');

        if (Schema::hasTable('media')) {
            $legacyMedia = DB::table('media')
                ->whereIn('collection', ['try-on-layer', 'try-on-mannequin'])
                ->get(['disk', 'path']);

            foreach ($legacyMedia as $media) {
                $path = (string) ($media->path ?? '');
                if ($path !== '' && !preg_match('/^https?:\\/\\//i', $path)) {
                    Storage::disk($media->disk ?: 'public')->delete($path);
                }
            }

            DB::table('media')
                ->whereIn('collection', ['try-on-layer', 'try-on-mannequin'])
                ->delete();
        }

        Schema::create('fitting_room_mannequins', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('label', 160);
            $table->string('audience', 16)->default('all')->index();
            $table->string('pose_key', 80)->default('front-neutral');
            $table->unsignedSmallInteger('canvas_width')->default(640);
            $table->unsignedSmallInteger('canvas_height')->default(960);
            $table->foreignId('base_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('fitting_room_looks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mannequin_id')->constrained('fitting_room_mannequins')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('label', 160);
            $table->foreignId('preview_top_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('preview_bottom_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('preview_combined_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('preview_suggested_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['mannequin_id', 'code']);
            $table->index(['mannequin_id', 'is_active', 'sort_order']);
        });

        Schema::create('fitting_room_look_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('look_id')->constrained('fitting_room_looks')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('slot', 16);
            $table->decimal('fit_y', 4, 3)->default(0.380);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['look_id', 'slot']);
            $table->index(['product_variant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitting_room_look_items');
        Schema::dropIfExists('fitting_room_looks');
        Schema::dropIfExists('fitting_room_mannequins');

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

        Schema::create('try_on_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('try_on_mannequin_id')->constrained('try_on_mannequins')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('slot', 24);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['try_on_mannequin_id', 'slot', 'is_active']);
            $table->index(['try_on_mannequin_id', 'slot', 'is_default', 'is_active'], 'try_on_assets_default_slot_index');
        });
    }
};
