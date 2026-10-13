<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        // A prior interrupted/manual run may have added the column even though
        // Laravel did not record this migration in the migrations table.
        if (! Schema::hasColumn('products', 'is_hero')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->boolean('is_hero')
                    ->default(false)
                    ->after('is_featured')
                    ->index();
            });
        } elseif (! Schema::hasIndex('products', ['is_hero'])) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index('is_hero');
            });
        }

        // Do not fail a deployment on a database where hero slides have not
        // been installed yet. The backfill remains safe to re-run.
        if (! Schema::hasTable('hero_slides')
            || ! Schema::hasColumn('hero_slides', 'is_active')
            || ! Schema::hasColumn('hero_slides', 'product_id')) {
            return;
        }

        $heroProductIds = DB::table('hero_slides')
            ->where('is_active', true)
            ->whereNotNull('product_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(6)
            ->pluck('product_id');

        if ($heroProductIds->isNotEmpty()) {
            DB::table('products')
                ->whereIn('id', $heroProductIds)
                ->update(['is_hero' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'is_hero')) {
            return;
        }

        $hasHeroIndex = Schema::hasIndex('products', ['is_hero']);

        Schema::table('products', function (Blueprint $table) use ($hasHeroIndex): void {
            if ($hasHeroIndex) {
                $table->dropIndex(['is_hero']);
            }

            $table->dropColumn('is_hero');
        });
    }
};
