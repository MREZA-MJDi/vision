<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();

            $table->string('order_number')->unique();

            // Snapshot customer information
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();

            // Snapshot shipping information
            $table->text('shipping_address');
            $table->string('shipping_province', 100)->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            $table->string('status')
                ->default('pending')
                ->index();

            $table->string('payment_status')
                ->default('pending')
                ->index();

            $table->string('payment_method')
                ->nullable();

            $table->decimal('subtotal', 14, 2)
                ->default(0);

            $table->decimal('discount', 14, 2)
                ->default(0);

            $table->decimal('shipping_cost', 14, 2)
                ->default(0);

            $table->decimal('total', 14, 2)
                ->default(0);

            $table->text('customer_note')->nullable();

            $table->string('tracking_code')
                ->nullable()
                ->index();

            $table->timestamp('placed_at')
                ->nullable()
                ->index();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamp('shipped_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['payment_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
