<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MakeAdminCommand extends Command
{
    protected $signature = 'commerce-admin:make-admin {email : Email address for the administrator} {--create : Create an account if no user exists (interactive only)}';

    protected $description = 'Promote an existing user or explicitly create a commerce administrator account.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('A valid email address is required.');

            return self::FAILURE;
        }

        $model = config('commerce-admin.models.user');
        if (! is_string($model) || ! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            $this->components->error('The configured commerce-admin user model is not a valid Eloquent model.');

            return self::FAILURE;
        }

        $column = (string) config('commerce-admin.admin_flag_column', 'is_admin');
        $prototype = new $model();
        $table = $prototype->getTable();

        if (! Schema::hasColumn($table, 'email') || ! Schema::hasColumn($table, $column)) {
            $this->components->error("The user table [{$table}] must contain the email and [{$column}] columns.");

            return self::FAILURE;
        }

        $user = $model::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user) {
            if ((bool) $user->getAttribute($column)) {
                $this->components->info("{$email} already has commerce administrator access.");

                return self::SUCCESS;
            }

            $user->forceFill([$column => true])->save();
            $this->components->info("Administrator access enabled for {$user->email}. Existing account data was preserved.");

            return self::SUCCESS;
        }

        if (! $this->option('create')) {
            $this->components->error(
                "No user was found for {$email}. No account was created. " .
                'Create the account in your normal registration/admin flow, or rerun with --create to create it interactively.'
            );

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('The --create option requires interactive input so the password is not passed in shell arguments.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn($table, 'name') || ! Schema::hasColumn($table, 'password')) {
            $this->components->error("The user table [{$table}] must contain name and password columns to create an account.");

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Admin name'));
        if ($name === '' || strlen($name) > 255) {
            $this->components->error('The admin name must contain between 1 and 255 characters.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Admin password (minimum 12 characters)');
        if (strlen($password) < 12) {
            $this->components->error('The admin password must be at least 12 characters. No account was created.');

            return self::FAILURE;
        }

        $confirmation = (string) $this->secret('Confirm admin password');
        if (! hash_equals($password, $confirmation)) {
            $this->components->error('The passwords do not match. No account was created.');

            return self::FAILURE;
        }

        $user = new $model();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            $column => true,
        ])->save();

        $this->components->info("Commerce administrator account created for {$email}.");

        return self::SUCCESS;
    }
}
