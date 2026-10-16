<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('try_on_assets', function (Blueprint $table): void {
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->index(['try_on_mannequin_id', 'slot', 'is_default', 'is_active'], 'try_on_assets_default_slot_index');
        });
    }

    public function down(): void
    {
        Schema::table('try_on_assets', function (Blueprint $table): void {
            $table->dropIndex('try_on_assets_default_slot_index');
            $table->dropColumn('is_default');
        });
    }
};
