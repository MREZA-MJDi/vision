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
        $obsoleteMedia = Schema::hasTable('media')
            ? DB::table('media')
                ->whereIn('collection', ['try-on-layer', 'try-on-base', 'try-on-mannequin'])
                ->get(['disk', 'path'])
            : collect();

        foreach ($obsoleteMedia as $media) {
            $path = trim((string) $media->path);
            if ($path === '' || preg_match('~^https?://~i', $path)) {
                continue;
            }

            try {
                Storage::disk($media->disk ?: config('filesystems.default', 'local'))->delete($path);
            } catch (Throwable) {
                // Schema cleanup must still complete if an old media disk is offline.
            }
        }

        Schema::dropIfExists('try_on_assets');
        Schema::dropIfExists('try_on_mannequins');

        if (Schema::hasTable('media')) {
            DB::table('media')
                ->whereIn('collection', ['try-on-layer', 'try-on-base', 'try-on-mannequin'])
                ->delete();
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'audience')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex(['audience']);
                $table->dropColumn('audience');
            });
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: this removes feature-only tables and uploads.
    }
};
