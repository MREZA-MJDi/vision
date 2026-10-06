<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sayad_id', 16)->unique();
            $table->string('cheque_number', 100)->nullable();
            $table->string('bank_name', 120);
            $table->string('account_holder', 160)->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('due_date');
            $table->string('image_path', 500)->nullable();

            $table->string('status', 30)
                ->default('submitted')
                ->index();

            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamp('deposited_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('bounced_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['bank_name', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_payments');
    }
};
