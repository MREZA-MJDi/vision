<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_skips_when_no_bootstrap_credentials_are_configured(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => null,
            'commerce-admin.bootstrap_admin.password' => null,
        ]);

        $this->seed(AdminSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_creates_a_hashed_admin_and_is_idempotent(): void
    {
        $password = 'CorrectHorseBatteryStaple123!';
        config([
            'commerce-admin.bootstrap_admin.email' => 'owner@example.test',
            'commerce-admin.bootstrap_admin.name' => 'Store Owner',
            'commerce-admin.bootstrap_admin.password' => $password,
        ]);

        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $user = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->assertTrue((bool) $user->is_admin);
        $this->assertSame('Store Owner', $user->name);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_seeder_promotes_existing_user_without_overwriting_their_details(): void
    {
        $user = User::query()->create([
            'name' => 'Existing Account Name',
            'email' => 'existing-owner@example.test',
            'password' => 'existing-password-123',
        ]);
        $user->forceFill(['is_admin' => false])->save();

        config([
            'commerce-admin.bootstrap_admin.email' => 'EXISTING-OWNER@example.test',
            'commerce-admin.bootstrap_admin.name' => 'Ignored New Name',
            'commerce-admin.bootstrap_admin.password' => 'IgnoredNewPassword123!',
        ]);

        $this->seed(AdminSeeder::class);

        $user->refresh();
        $this->assertTrue((bool) $user->is_admin);
        $this->assertSame('Existing Account Name', $user->name);
        $this->assertTrue(Hash::check('existing-password-123', $user->password));
        $this->assertSame(1, User::query()->count());
    }

    public function test_seeder_rejects_weak_password_for_a_new_account(): void
    {
        config([
            'commerce-admin.bootstrap_admin.email' => 'weak@example.test',
            'commerce-admin.bootstrap_admin.name' => 'Weak Admin',
            'commerce-admin.bootstrap_admin.password' => 'short',
        ]);

        try {
            $this->seed(AdminSeeder::class);
            $this->fail('The seeder should reject a weak bootstrap password.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('at least 12 characters', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }
}
