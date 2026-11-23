<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_is_public(): void
    {
        $this->withoutVite();
        $this->get('/contact')->assertOk()->assertSee('ارسال پیام');
    }

    public function test_contact_form_persists_valid_messages(): void
    {
        $this->post('/contact', [
            'name' => 'Customer Example',
            'email' => 'customer@example.com',
            'subject' => 'Order question',
            'message' => 'Please help me check the status of my order.',
        ])->assertRedirect(route('contact.index'));

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'customer@example.com',
            'status' => 'new',
            'subject' => 'Order question',
        ]);
    }

    public function test_contact_form_rejects_missing_reply_channel(): void
    {
        $this->post('/contact', [
            'name' => 'Customer Example',
            'subject' => 'Question',
            'message' => 'Please help me with my order.',
        ])->assertSessionHasErrors(['email', 'phone']);

        $this->assertSame(0, DB::table('contact_messages')->count());
    }
}
