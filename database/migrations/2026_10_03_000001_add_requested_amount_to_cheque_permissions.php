<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheque_permissions', function (Blueprint $table): void {
            $table->decimal('requested_amount', 14, 2)->nullable()->after('requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('cheque_permissions', function (Blueprint $table): void {
            $table->dropColumn('requested_amount');
        });
    }
};
