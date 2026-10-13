<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'is_taxable')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->boolean('is_taxable')->default(false);
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'tax')) {
                    $table->decimal('tax', 12, 2)->default(0);
                }
                if (! Schema::hasColumn('orders', 'out_for_delivery_at')) {
                    $table->timestamp('out_for_delivery_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'tax_amount')) {
            Schema::table('order_items', function (Blueprint $table): void {
                $table->decimal('tax_amount', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'tax_amount')) {
            Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('tax_amount'));
        }
        if (Schema::hasTable('orders')) {
            $columns = array_values(array_filter(['tax', 'out_for_delivery_at'], fn (string $column) => Schema::hasColumn('orders', $column)));
            if ($columns) {
                Schema::table('orders', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'is_taxable')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('is_taxable'));
        }
    }
};
