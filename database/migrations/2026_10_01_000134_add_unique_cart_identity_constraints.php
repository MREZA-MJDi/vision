<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->deduplicateCarts('user_id');
            $this->deduplicateCarts('session_id');

            $indexes = collect(Schema::getIndexes('carts'));

            $hasUserUnique = $indexes->contains(
                fn (array $index): bool =>
                    (bool) ($index['unique'] ?? false)
                    && array_values($index['columns'] ?? []) === ['user_id']
            );

            if (! $hasUserUnique) {
                Schema::table('carts', function (Blueprint $table): void {
                    $table->unique('user_id', 'carts_user_id_unique');
                });
            }

            $indexes = collect(Schema::getIndexes('carts'));

            $hasSessionUnique = $indexes->contains(
                fn (array $index): bool =>
                    (bool) ($index['unique'] ?? false)
                    && array_values($index['columns'] ?? []) === ['session_id']
            );

            if (! $hasSessionUnique) {
                Schema::table('carts', function (Blueprint $table): void {
                    $table->unique('session_id', 'carts_session_id_unique');
                });
            }
        });
    }

    private function deduplicateCarts(string $column): void
    {
        $duplicateValues = DB::table('carts')
            ->whereNotNull($column)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($column);

        foreach ($duplicateValues as $value) {
            $carts = DB::table('carts')
                ->where($column, $value)
                ->orderBy('id')
                ->get();

            $keeper = $carts->first();

            if (! $keeper) {
                continue;
            }

            foreach ($carts->skip(1) as $duplicate) {
                $items = DB::table('cart_items')
                    ->where('cart_id', $duplicate->id)
                    ->orderBy('id')
                    ->get();

                foreach ($items as $item) {
                    $existing = DB::table('cart_items')
                        ->where('cart_id', $keeper->id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->first();

                    if ($existing) {
                        DB::table('cart_items')
                            ->where('id', $existing->id)
                            ->update([
                                'quantity' => (int) $existing->quantity + (int) $item->quantity,
                                'updated_at' => now(),
                            ]);

                        DB::table('cart_items')
                            ->where('id', $item->id)
                            ->delete();

                        continue;
                    }

                    DB::table('cart_items')
                        ->where('id', $item->id)
                        ->update([
                            'cart_id' => $keeper->id,
                            'updated_at' => now(),
                        ]);
                }

                DB::table('carts')
                    ->where('id', $duplicate->id)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('carts'));

        $userUnique = $indexes->first(
            fn (array $index): bool =>
                (bool) ($index['unique'] ?? false)
                && array_values($index['columns'] ?? []) === ['user_id']
        );

        if ($userUnique) {
            Schema::table('carts', function (Blueprint $table) use ($userUnique): void {
                $table->dropUnique($userUnique['name']);
            });
        }

        $indexes = collect(Schema::getIndexes('carts'));

        $sessionUnique = $indexes->first(
            fn (array $index): bool =>
                (bool) ($index['unique'] ?? false)
                && array_values($index['columns'] ?? []) === ['session_id']
        );

        if ($sessionUnique) {
            Schema::table('carts', function (Blueprint $table) use ($sessionUnique): void {
                $table->dropUnique($sessionUnique['name']);
            });
        }
    }
};
