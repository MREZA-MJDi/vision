<?php

namespace Rm\\CommerceAdmin\\Console;

use Illuminate\\Console\\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'commerce-admin:make-admin {email : Email address of an existing user}';
    protected $description = 'Promote an existing user to commerce administrator using the configured role flag.';

    public function handle(): int
    {
        $model = config('commerce-admin.models.user');
        $user = $model::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->components->error('No user was found with that email. Create the user first, then run this command again.');

            return self::FAILURE;
        }

        $column = config('commerce-admin.admin_flag_column', 'is_admin');
        $user->forceFill([$column => true])->save();

        $this->components->info('Administrator access enabled for ' . $user->email . '. No account or commerce records were created.');

        return self::SUCCESS;
    }
}
