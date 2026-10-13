<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This is a data backfill, not a schema migration.
     *
     * The products table already owns is_hero in its initial migration.
     * Keeping a second add-column operation here made fresh SQLite/MySQL
     * installs fail with "duplicate column" before any feature tests ran.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('products')
            || ! Schema::hasColumn('products', 'is_hero')
            || ! Schema::hasTable('hero_slides')
            || ! Schema::hasColumn('hero_slides', 'is_active')
            || ! Schema::hasColumn('hero_slides', 'sort_order')
            || ! Schema::hasColumn('hero_slides', 'product_id')
        ) {
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

    /**
     * Do not remove is_hero on rollback: the column belongs to the original
     * products-table migration, not to this legacy data backfill.
     */
    public function down(): void
    {
        // Intentionally empty; this migration only copies existing data.
    }
};
