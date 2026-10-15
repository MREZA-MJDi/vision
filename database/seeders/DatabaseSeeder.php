<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Launch data is real application data: it is editable in Commerce
        // Admin and respects any later edit, archive, or media deletion.
        $this->call([AdminSeeder::class, StorefrontLaunchSeeder::class, VirtualTryOnMannequinSeeder::class]);
    }
}
