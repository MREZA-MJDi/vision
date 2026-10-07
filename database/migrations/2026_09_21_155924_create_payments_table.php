<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('gateway', 50);

            $table->string('transaction_id')
                ->nullable();

            $table->string('authority')
                ->nullable();

            $table->string('reference_number')
                ->nullable();

            $table->decimal('amount', 14, 2);

            $table->string('status', 30)
                ->default('pending')
                ->index();

            $table->json('metadata')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamps();

            $table->index('transaction_id');

            $table->unique([
                'gateway',
                'transaction_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
