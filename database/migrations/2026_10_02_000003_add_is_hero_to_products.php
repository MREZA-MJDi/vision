<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_hero')
                ->default(false)
                ->index()
                ->after('is_featured');
        });

        $heroProductIds = DB::table('hero_slides')
            ->where('is_active', true)
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
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['is_hero']);
            $table->dropColumn('is_hero');
        });
    }
};
