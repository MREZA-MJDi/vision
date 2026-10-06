<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheque_permissions', function (Blueprint $table): void {
            $table->timestamp('requested_at')->nullable()->after('max_order_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cheque_permissions', function (Blueprint $table): void {
            $table->dropColumn('requested_at');
        });
    }
};
