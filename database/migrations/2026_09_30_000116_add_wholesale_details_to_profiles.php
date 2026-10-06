<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'wholesale_profiles_status_minimums_index';

    public function up(): void
    {
        if (! Schema::hasColumn('wholesale_profiles', 'business_name')) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->string('business_name', 160)->nullable()->after('user_id');
            });
        }

        if (! Schema::hasColumn('wholesale_profiles', 'business_type')) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->string('business_type', 120)->nullable()->after('business_name');
            });
        }

        if (! Schema::hasColumn('wholesale_profiles', 'business_phone')) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->string('business_phone', 30)->nullable()->after('business_type');
            });
        }

        if (! Schema::hasColumn('wholesale_profiles', 'business_address')) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->text('business_address')->nullable()->after('business_phone');
            });
        }

        if (! Schema::hasColumn('wholesale_profiles', 'minimum_order_amount')) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->decimal('minimum_order_amount', 14, 2)
                    ->nullable()
                    ->after('admin_note');
            });
        }

        if (! Schema::hasColumn('wholesale_profiles', 'minimum_order_quantity')) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->unsignedInteger('minimum_order_quantity')
                    ->nullable()
                    ->after('minimum_order_amount');
            });
        }

        $indexes = Schema::getIndexes('wholesale_profiles');

        if (! collect($indexes)->contains(
            fn (array $index): bool => ($index['name'] ?? null) === self::INDEX_NAME
        )) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->index(
                    ['status', 'minimum_order_amount', 'minimum_order_quantity'],
                    self::INDEX_NAME
                );
            });
        }
    }

    public function down(): void
    {
        $indexes = Schema::getIndexes('wholesale_profiles');

        if (collect($indexes)->contains(
            fn (array $index): bool => ($index['name'] ?? null) === self::INDEX_NAME
        )) {
            Schema::table('wholesale_profiles', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        }

        $columns = [
            'business_name',
            'business_type',
            'business_phone',
            'business_address',
            'minimum_order_amount',
            'minimum_order_quantity',
        ];

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('wholesale_profiles', $column)
        ));

        if ($existingColumns !== []) {
            Schema::table('wholesale_profiles', function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
