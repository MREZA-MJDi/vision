<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'public_token')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->uuid('public_token')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'public_token')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropUnique(['public_token']);
                $table->dropColumn('public_token');
            });
        }
    }
};
