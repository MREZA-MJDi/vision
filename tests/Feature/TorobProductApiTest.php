<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TorobProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_torob_product_api_rejects_requests_without_a_valid_signed_token(): void
    {
        $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->postJson('/api/torob_api/v3/products', [
                'page' => 1,
                'sort' => 'date_added_desc',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized.');
    }

    public function test_torob_product_api_returns_active_variants_using_v3_fields(): void
    {
        $this->withoutVite();
        $this->seed(\Database\Seeders\CommerceDemoSeeder::class);
        $token = $this->signedTorobToken('localhost');

        $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->withHeaders([
                'X-Torob-Token' => $token,
                'X-Torob-Token-Version' => '1',
            ])
            ->postJson('/api/torob_api/v3/products', [
                'page' => 1,
                'sort' => 'date_added_desc',
            ]);

        $response->assertOk()
            ->assertJsonPath('api_version', 'torob_api_v3')
            ->assertJsonPath('current_page', 1)
            ->assertJsonStructure([
                'api_version',
                'current_page',
                'total',
                'max_pages',
                'next_cursor',
                'products' => [
                    '*' => [
                        'page_unique',
                        'page_url',
                        'product_group_id',
                        'title',
                        'current_price',
                        'availability',
                        'image_links',
                        'spec',
                        'date_added',
                        'date_updated',
                    ],
                ],
            ]);

        $products = $response->json('products');
        $this->assertNotEmpty($products, 'The demo seeder should provide at least one product variant with a real image.');
        $this->assertMatchesRegularExpression('/^[1-9][0-9]*_[1-9][0-9]*$/', $products[0]['page_unique']);
        $this->assertNotEmpty($products[0]['image_links']);
        $this->assertStringContainsString('/products/', $products[0]['page_url']);
        $this->assertSame('http', parse_url($products[0]['page_url'], PHP_URL_SCHEME));
    }

    public function test_torob_page_url_lookup_returns_the_requested_variant(): void
    {
        $this->withoutVite();
        $this->seed(\Database\Seeders\CommerceDemoSeeder::class);

        $product = Product::query()
            ->where('slug', 'demo-essential-cotton-tee')
            ->with('activeVariants')
            ->firstOrFail();
        $variant = $product->activeVariants->firstOrFail();
        $url = route('products.show', [
            'product' => $product->slug,
            'variant_id' => $variant->getKey(),
        ]);

        $token = $this->signedTorobToken('localhost');
        $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->withHeaders([
                'X-Torob-Token' => $token,
                'X-Torob-Token-Version' => '1',
            ])
            ->postJson('/api/torob_api/v3/products', [
                'page_urls' => [$url],
            ]);

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('products.0.page_unique', $product->getKey() . '_' . $variant->getKey())
            ->assertJsonPath('products.0.product_group_id', (string) $product->getKey());
    }

    private function signedTorobToken(string $audience): string
    {
        $keyPair = sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        config([
            'services.torob.product_api_enabled' => true,
            'services.torob.public_key' => base64_encode($publicKey),
        ]);

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'EdDSA',
            'typ' => 'JWT',
            'v' => 1,
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 300,
            'nbf' => time() - 5,
        ], JSON_THROW_ON_ERROR));

        $message = $header . '.' . $payload;
        $signature = sodium_crypto_sign_detached($message, $secretKey);

        return $message . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
