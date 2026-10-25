<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeContactLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_home_contact_links_and_the_homepage_renders_safe_brand_icons(): void
    {
        $admin = User::query()->create([
            'name' => 'Contact Settings Admin',
            'email' => 'contact-settings-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->put(route('commerce-admin.contact.update'), [
                'instagram_url' => 'https://www.instagram.com/vision.test/',
                'telegram_url' => 'https://t.me/vision_test',
                'phone' => '+98 912 123 4567',
            ])
            ->assertRedirect(route('commerce-admin.dashboard'));

        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact.instagram',
            'value' => 'https://www.instagram.com/vision.test/',
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact.telegram',
            'value' => 'https://t.me/vision_test',
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'contact.phone',
            'value' => '+98 912 123 4567',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('home-minimal-footer__contact', false)
            ->assertSee('aria-label="صفحهٔ رسمی VISION در اینستاگرام"', false)
            ->assertSee('aria-label="ارتباط با VISION در تلگرام"', false)
            ->assertSee('aria-label="تماس تلفنی با VISION"', false)
            ->assertSee('href="tel:+989121234567"', false)
            ->assertSee('type="application/ld+json"', false)
            ->assertSee('https://www.instagram.com/vision.test/', false)
            ->assertSee('https://t.me/vision_test', false);
    }

    public function test_social_link_domain_validation_happens_before_any_setting_is_written(): void
    {
        $admin = User::query()->create([
            'name' => 'Invalid Contact Admin',
            'email' => 'invalid-contact-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->from(route('commerce-admin.dashboard'))
            ->put(route('commerce-admin.contact.update'), [
                'instagram_url' => 'https://www.instagram.com/vision.test/',
                'telegram_url' => 'https://example.com/not-telegram',
                'phone' => '09121234567',
            ])
            ->assertRedirect(route('commerce-admin.dashboard'))
            ->assertSessionHasErrors('telegram_url');

        $this->assertDatabaseMissing('site_settings', [
            'key' => 'contact.instagram',
            'value' => 'https://www.instagram.com/vision.test/',
        ]);
        $this->assertDatabaseMissing('site_settings', [
            'key' => 'contact.phone',
            'value' => '09121234567',
        ]);
    }

    public function test_homepage_hides_contact_icons_when_the_admin_has_not_configured_them(): void
    {
        $this->withoutVite();

        SiteSetting::query()->delete();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('home-contact-icon--instagram', false)
            ->assertDontSee('home-contact-icon--telegram', false)
            ->assertDontSee('home-contact-icon--phone', false)
            ->assertDontSee('application/ld+json', false);
    }
}
