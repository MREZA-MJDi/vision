<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedStorefrontAdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_from_the_storefront_login_and_reach_the_live_dashboard(): void
    {
        $admin = $this->makeAdmin('shared-admin@example.test');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('login.store'), false)
            ->assertSee('Access is routed according to your account permissions.');

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'admin-password-123',
        ])->assertRedirect(route('commerce-admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->get(route('commerce-admin.dashboard'))
            ->assertOk()
            ->assertSee('داشبورد فروشگاه')
            ->assertSee('درآمد پرداخت‌شده')
            ->assertSee('آخرین سفارش‌ها');
    }

    public function test_customer_uses_the_same_login_but_cannot_enter_admin_pages(): void
    {
        $customer = $this->makeCustomer('shared-customer@example.test');

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'customer-password-123',
        ])->assertRedirect(route('account.show'));

        $this->assertAuthenticatedAs($customer);
        $this->get(route('account.show'))->assertOk();

        $this->get(route('commerce-admin.dashboard'))->assertForbidden();

        // A denied admin request must not unexpectedly log a customer out.
        $this->assertAuthenticatedAs($customer);
        $this->get(route('account.show'))->assertOk();
    }

    public function test_legacy_admin_login_url_redirects_to_the_single_storefront_form(): void
    {
        $this->get(route('commerce-admin.login'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('login.store'), false);
    }

    public function test_failed_login_uses_a_role_neutral_error_message(): void
    {
        $admin = $this->makeAdmin('neutral-error-admin@example.test');

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'not-the-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'These credentials could not be verified.',
            ]);

        $this->assertGuest();
    }

    public function test_public_registration_cannot_assign_admin_privileges(): void
    {
        $email = 'self-promoted@example.test';

        $this->post(route('register.store'), [
            'name' => 'Self Promoted Attempt',
            'email' => $email,
            'password' => 'customer-password-123',
            'password_confirmation' => 'customer-password-123',
            'terms' => '1',
            'is_admin' => '1',
        ])->assertRedirect(route('account.show'));

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'is_admin' => false,
        ]);

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->get(route('commerce-admin.dashboard'))->assertForbidden();
    }

    public function test_customer_redirect_does_not_follow_a_stale_admin_intended_url(): void
    {
        $customer = $this->makeCustomer('safe-redirect-customer@example.test');

        $this->withSession(['url.intended' => route('commerce-admin.dashboard')])
            ->post(route('login.store'), [
                'email' => $customer->email,
                'password' => 'customer-password-123',
            ])
            ->assertRedirect(route('account.show'));

        $this->assertAuthenticatedAs($customer);
    }

    private function makeAdmin(string $email): User
    {
        $admin = $this->makeCustomer($email);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin->fresh();
    }

    private function makeCustomer(string $email): User
    {
        return User::query()->create([
            'name' => 'Test Account',
            'email' => $email,
            'password' => str_contains($email, 'admin')
                ? 'admin-password-123'
                : 'customer-password-123',
            'is_admin' => false,
        ]);
    }
}
