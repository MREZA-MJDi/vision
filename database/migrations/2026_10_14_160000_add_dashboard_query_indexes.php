<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The dashboard sorts the latest records by created_at and repeatedly
        // reads small status/metric projections from these tables.
        Schema::table('orders', function (Blueprint $table): void {
            $table->index('created_at', 'orders_created_at_index');
            // A narrow covering index can serve the dashboard's full-table
            // conditional aggregates without reading wide customer/address rows.
            $table->index(
                ['payment_status', 'status', 'total'],
                'orders_dashboard_metrics_index'
            );
        });

        Schema::table('users', function (Blueprint $table): void {
            // Supports both the non-admin customer count and the newest-customer list.
            $table->index(['is_admin', 'created_at'], 'users_admin_created_index');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            // Narrows the active variants scanned by the low-stock indicator.
            $table->index(
                ['is_active', 'stock', 'low_stock_threshold'],
                'variants_active_stock_threshold_index'
            );
        });

        if (Schema::hasTable('product_reviews')) {
            Schema::table('product_reviews', function (Blueprint $table): void {
                // Helps the single grouped review-status/rating aggregate.
                $table->index(['status', 'rating'], 'reviews_status_rating_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_reviews')) {
            Schema::table('product_reviews', function (Blueprint $table): void {
                $table->dropIndex('reviews_status_rating_index');
            });
        }

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropIndex('variants_active_stock_threshold_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_admin_created_index');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_created_at_index');
            $table->dropIndex('orders_dashboard_metrics_index');
        });
    }
};
