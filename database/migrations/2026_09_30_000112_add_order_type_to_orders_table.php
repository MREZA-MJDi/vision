<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type', 20)
                ->default('retail')
                ->after('payment_method')
                ->index();

            $table->index(['order_type', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_type', 'payment_status']);
            $table->dropIndex(['order_type']);
            $table->dropColumn('order_type');
        });
    }
};
