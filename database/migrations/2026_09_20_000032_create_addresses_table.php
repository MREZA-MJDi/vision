<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title', 100);

            $table->string('recipient_name');
            $table->string('recipient_phone', 30);

            $table->string('province', 100);
            $table->string('city', 100);

            $table->string('postal_code', 20)
                ->nullable();

            $table->text('address');

            $table->string('plaque', 20)
                ->nullable();

            $table->string('unit', 20)
                ->nullable();

            $table->boolean('is_default')
                ->default(false)
                ->index();

            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
