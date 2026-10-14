<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // No demo catalog data is injected into the live commerce database.
        // The admin bootstrap is environment-driven and does not overwrite an existing password.
        $this->call(AdminSeeder::class);
    }
}
