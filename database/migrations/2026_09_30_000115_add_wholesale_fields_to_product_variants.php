<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->decimal('wholesale_price', 14, 2)
                ->nullable()
                ->after('sale_price');

            $table->index('wholesale_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropIndex(['wholesale_price']);
            $table->dropColumn('wholesale_price');
        });
    }
};
