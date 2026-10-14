<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CommerceDemoSeeder extends Seeder
{
    public function run(): void
    {
        // This seeder is opt-in. It is deliberately not called by DatabaseSeeder,
        // so a normal production seed command never creates demo commerce data.
        $this->call(InventoryDemoSeeder::class);

        $this->command?->info('Commerce demo catalog, color variants, SVG images and inventory ledger are ready.');
    }
}
