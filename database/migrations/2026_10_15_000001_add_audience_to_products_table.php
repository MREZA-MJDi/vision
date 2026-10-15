<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // Nullable leaves existing products safely unclassified until curated.
            $table->string('audience', 16)->nullable()->index()->after('attributes');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['audience']);
            $table->dropColumn('audience');
        });
    }
};
