<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_promotes_an_existing_user_without_replacing_account_data(): void
    {
        $user = User::query()->create([
            'name' => 'Existing Customer',
            'email' => 'existing@example.test',
            'password' => 'customer-password-123',
            'is_admin' => false,
        ]);

        $this->artisan('commerce-admin:make-admin', [
            'email' => 'EXISTING@example.test',
        ])->assertExitCode(0);

        $user->refresh();

        $this->assertTrue($user->is_admin);
        $this->assertSame('Existing Customer', $user->name);
        $this->assertTrue(Hash::check('customer-password-123', $user->password));
        $this->assertSame(1, User::query()->count());
    }

    public function test_command_does_not_create_an_account_unless_create_option_is_explicit(): void
    {
        $this->artisan('commerce-admin:make-admin', [
            'email' => 'missing@example.test',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_create_option_interactively_creates_a_hashed_admin_account(): void
    {
        $password = 'VeryStrongAdminPassword123!';

        $this->artisan('commerce-admin:make-admin', [
            'email' => 'NEW-ADMIN@example.test',
            '--create' => true,
        ])
            ->expectsQuestion('Admin name', 'Store Administrator')
            ->expectsQuestion('Admin password (minimum 12 characters)', $password)
            ->expectsQuestion('Confirm admin password', $password)
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'Store Administrator',
            'email' => 'new-admin@example.test',
            'is_admin' => 1,
        ]);

        $user = User::query()->where('email', 'new-admin@example.test')->firstOrFail();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame(1, User::query()->count());
    }

    public function test_create_option_rejects_a_short_password_without_creating_an_account(): void
    {
        $this->artisan('commerce-admin:make-admin', [
            'email' => 'weak-admin@example.test',
            '--create' => true,
        ])
            ->expectsQuestion('Admin name', 'Weak Administrator')
            ->expectsQuestion('Admin password (minimum 12 characters)', 'short')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_fails_safely_when_the_configured_admin_flag_column_is_missing(): void
    {
        User::query()->create([
            'name' => 'Existing Customer',
            'email' => 'column-check@example.test',
            'password' => 'customer-password-123',
            'is_admin' => false,
        ]);

        config(['commerce-admin.admin_flag_column' => 'missing_admin_flag']);

        $this->artisan('commerce-admin:make-admin', [
            'email' => 'column-check@example.test',
        ])->assertExitCode(1);

        $this->assertDatabaseHas('users', [
            'email' => 'column-check@example.test',
            'is_admin' => 0,
        ]);
    }
}
