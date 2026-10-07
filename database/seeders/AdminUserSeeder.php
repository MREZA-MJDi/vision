<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = trim((string) env('ADMIN_PHONE'));
        $plain = (string) env('ADMIN_PASSWORD');
        $name = trim((string) env('ADMIN_NAME', 'Vision Admin'));
        $email = trim((string) env('ADMIN_EMAIL'));

        if ($phone === '' || $plain === '') {
            $this->command?->warn('ADMIN_PHONE and ADMIN_PASSWORD are not set; admin user was not created.');
            return;
        }

        $user = User::query()->firstOrNew(['phone' => $phone]);
        $user->name = $name !== '' ? $name : 'Vision Admin';
        $user->phone = $phone;
        $user->email = $email !== '' ? $email : null;
        $user->password = $plain;
        $user->is_admin = true;
        $user->save();

        $this->command?->info("Admin credentials synchronized: {$phone}");
    }
}
