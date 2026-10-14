<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) config('commerce-admin.bootstrap_admin.email', '')));
        $name = trim((string) config('commerce-admin.bootstrap_admin.name', ''));
        $password = (string) config('commerce-admin.bootstrap_admin.password', '');

        if ($email === '' && $password === '') {
            $this->command?->info('AdminSeeder skipped: set COMMERCE_ADMIN_EMAIL and COMMERCE_ADMIN_PASSWORD to bootstrap an administrator.');

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('AdminSeeder requires a valid COMMERCE_ADMIN_EMAIL.');
        }

        $model = config('commerce-admin.models.user');
        if (! is_string($model) || ! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            throw new RuntimeException('The configured commerce-admin user model is not a valid Eloquent model.');
        }

        $prototype = new $model();
        $table = $prototype->getTable();
        $adminColumn = (string) config('commerce-admin.admin_flag_column', 'is_admin');

        foreach (['email', $adminColumn] as $column) {
            if (! Schema::hasColumn($table, $column)) {
                throw new RuntimeException("AdminSeeder requires the [{$column}] column on [{$table}].");
            }
        }

        $user = $model::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user) {
            if (! (bool) $user->getAttribute($adminColumn)) {
                $user->forceFill([$adminColumn => true])->save();
                $this->command?->info("Existing account {$email} was granted administrator access.");
            } else {
                $this->command?->info("Administrator {$email} already exists; no account fields were changed.");
            }

            return;
        }

        if (! Schema::hasColumn($table, 'name') || ! Schema::hasColumn($table, 'password')) {
            throw new RuntimeException("AdminSeeder requires name and password columns on [{$table}] to create an account.");
        }

        if ($name === '' || strlen($name) > 255) {
            throw new RuntimeException('AdminSeeder requires COMMERCE_ADMIN_NAME with 1–255 characters.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('AdminSeeder requires COMMERCE_ADMIN_PASSWORD with at least 12 characters.');
        }

        $user = new $model();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            $adminColumn => true,
        ])->save();

        $this->command?->info("Commerce administrator {$email} created.");
    }
}
