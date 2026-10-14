<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class InventoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Keep this seeder independently runnable, but ensure its demo variants exist.
        $this->call(ProductDemoSeeder::class);

        $variants = ProductVariant::query()
            ->where('sku', 'like', 'VISION-DEMO-%')
            ->with('product')
            ->orderBy('id')
            ->get();

        foreach ($variants as $variant) {
            if (! $variant->product) {
                continue;
            }

            $note = 'Initial stock set by CommerceDemoSeeder.';
            $movement = InventoryMovement::query()->firstOrNew([
                'product_variant_id' => $variant->getKey(),
                'type' => 'adjustment',
                'reference_type' => $variant->product->getMorphClass(),
                'reference_id' => $variant->product->getKey(),
                'note' => $note,
            ]);

            $movement->fill([
                'quantity' => (int) $variant->stock,
                'stock_after' => (int) $variant->stock,
                'created_by' => null,
            ])->save();
        }

        $this->command?->info('Demo inventory balances have auditable adjustment records. Use the Inventory screen to adjust stock; ledger history stays immutable.');
    }
}
