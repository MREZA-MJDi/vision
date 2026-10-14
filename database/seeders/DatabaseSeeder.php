<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Production-safe by default: only bootstrap an admin when credentials
        // were explicitly provided. Optional catalog/demo seeders are run on
        // purpose, never as a side effect of the normal database seed command.
        $this->call(AdminSeeder::class);
    }
}
