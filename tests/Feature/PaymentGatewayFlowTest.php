<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_snappay_checkout_is_verified_and_settled_before_order_is_marked_paid(): void
    {
        config([
            'services.payment_gateways.snappay.enabled' => true,
            'services.payment_gateways.snappay.base_url' => 'https://api.snapppay.ir',
            'services.payment_gateways.snappay.client_id' => 'test-client',
            'services.payment_gateways.snappay.client_secret' => 'test-secret',
            'services.payment_gateways.snappay.username' => 'merchant-user',
            'services.payment_gateways.snappay.password' => 'merchant-password',
        ]);

        $expectedRial = 0;
        Http::fake(function (ClientRequest $request) use (&$expectedRial) {
            $url = $request->url();

            if (str_ends_with($url, '/api/online/v1/oauth/token')) {
                return Http::response(['access_token' => 'snap-access-token', 'expires_in' => 3600], 200);
            }
            if (str_ends_with($url, '/api/online/payment/v1/token')) {
                $expectedRial = (int) $request['amount'];
                return Http::response([
                    'successful' => true,
                    'response' => [
                        'paymentToken' => 'snap-payment-token',
                        'paymentPageUrl' => 'https://web.snapppay.ir/pay/test-token',
                    ],
                ], 200);
            }
            if (str_ends_with($url, '/api/online/payment/v1/verify')) {
                return Http::response([
                    'successful' => true,
                    'response' => [
                        'amount' => $expectedRial,
                        'transactionId' => 'VS-SNAPP-TRANSACTION',
                        'referenceNumber' => 'SNAPP-REFERENCE',
                    ],
                ], 200);
            }
            if (str_ends_with($url, '/api/online/payment/v1/settle')) {
                return Http::response(['successful' => true, 'response' => []], 200);
            }

            return Http::response(['error' => 'unexpected test request'], 500);
        });

        [, $variant] = $this->createProduct(price: 12.50, salePrice: 10.00, stock: 3);
        $this->postJson('/cart/items', ['variant_id' => $variant->getKey(), 'quantity' => 1])
            ->assertCreated();

        $response = $this->post('/checkout', [
            'customer_name' => 'Snap Test Buyer',
            'customer_email' => 'snap@example.test',
            'customer_phone' => '۰۹۱۲۳۴۵۶۷۸۹',
            'shipping_address' => 'Test street 12',
            'shipping_city' => 'Tehran',
            'payment_method' => 'snappay',
        ]);

        $response->assertSessionHasNoErrors();
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->where('order_id', $order->getKey())->firstOrFail();

        $response->assertRedirect('https://web.snapppay.ir/pay/test-token');
        $this->assertSame(100, $expectedRial, 'The Toman order total must be converted to Rial for the provider.');
        $this->assertSame('snappay', $payment->gateway);
        $this->assertSame('snap-payment-token', $payment->authority);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(0, CartItem::query()->count(), 'The cart is consumed only after the provider creates a hosted payment.');

        $this->get(route('payments.callback', [
            'gateway' => 'snappay',
            'token' => $order->public_token,
            'paymentToken' => 'snap-payment-token',
        ]))->assertRedirect(route('checkout.complete', ['token' => $order->public_token]))
          ->assertSessionHas('status');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('SNAPP-REFERENCE', $payment->fresh()->reference_number);
    }

    public function test_digipay_callback_does_not_mark_an_order_paid_until_the_provider_verifies_it(): void
    {
        config([
            'services.payment_gateways.digipay.enabled' => true,
            'services.payment_gateways.digipay.base_url' => 'https://uat.mydigipay.info',
            'services.payment_gateways.digipay.sandbox' => true,
            'services.payment_gateways.digipay.client_id' => 'test-client',
            'services.payment_gateways.digipay.client_secret' => 'test-secret',
            'services.payment_gateways.digipay.username' => 'merchant-user',
            'services.payment_gateways.digipay.password' => 'merchant-password',
            'services.payment_gateways.digipay.ticket_type' => 11,
        ]);

        $expectedRial = 0;
        Http::fake(function (ClientRequest $request) use (&$expectedRial) {
            $url = $request->url();

            if (str_ends_with($url, '/digipay/api/oauth/token')) {
                return Http::response(['access_token' => 'digi-access-token', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, '/digipay/api/tickets/business')) {
                $expectedRial = (int) $request['amount'];
                return Http::response([
                    'ticket' => 'digi-ticket',
                    'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:test-ticket',
                    'result' => ['status' => 0, 'message' => 'SUCCESS'],
                ], 200);
            }
            if (str_contains($url, '/digipay/api/purchases/verify/')) {
                return Http::response([
                    'trackingCode' => 'DIGI-TRACKING',
                    'amount' => (string) $expectedRial,
                    'rrn' => 'DIGI-RRN',
                    'result' => ['status' => 0, 'message' => 'SUCCESS'],
                ], 200);
            }

            return Http::response(['error' => 'unexpected test request'], 500);
        });

        [, $variant] = $this->createProduct(price: 25.00, salePrice: null, stock: 2);
        $this->postJson('/cart/items', ['variant_id' => $variant->getKey(), 'quantity' => 1])
            ->assertCreated();

        $response = $this->post('/checkout', [
            'customer_name' => 'Digi Test Buyer',
            'customer_email' => 'digi@example.test',
            'customer_phone' => '09123456789',
            'shipping_address' => 'Test street 14',
            'payment_method' => 'digipay',
        ]);
        $response->assertSessionHasNoErrors();
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->where('order_id', $order->getKey())->firstOrFail();

        $response->assertRedirect('https://uatweb.mydigipay.info/web-pay/tgs/v2:test-ticket');
        $this->assertSame('pending', $payment->status);
        $this->assertSame(250, $expectedRial);

        // A success-looking browser callback is only a signal to verify. The
        // server-to-server verification response is the source of truth.
        $this->post(route('payments.callback', [
            'gateway' => 'digipay',
            'token' => $order->public_token,
        ]), [
            'providerId' => $order->order_number,
            'trackingCode' => 'DIGI-TRACKING',
            'amount' => $expectedRial,
            'result' => 'SUCCESS',
            'type' => '11',
            'rrn' => 'DIGI-RRN',
        ])->assertRedirect(route('checkout.complete', ['token' => $order->public_token]))
          ->assertSessionHas('status');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('DIGI-RRN', $payment->fresh()->reference_number);
    }

    public function test_an_enabled_gateway_without_all_credentials_is_not_offered_at_checkout(): void
    {
        config([
            'services.payment_gateways.torobpay.enabled' => true,
            'services.payment_gateways.torobpay.client_id' => '',
            'services.payment_gateways.torobpay.client_secret' => '',
            'services.payment_gateways.torobpay.username' => '',
            'services.payment_gateways.torobpay.password' => '',
        ]);

        $this->assertArrayNotHasKey('torobpay', app(\App\Services\PaymentGatewayService::class)->availableGateways());
    }

    private function createProduct(float $price, ?float $salePrice, int $stock): array
    {
        $category = Category::query()->create([
            'name' => 'Test Category',
            'slug' => 'test-category-' . uniqid(),
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'short_description' => 'Test description',
            'is_active' => true,
            'is_taxable' => false,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'SKU-' . strtoupper(substr(str_replace('.', '', uniqid('', true)), -10)),
            'size' => 'M',
            'color' => 'Black',
            'price' => $price,
            'sale_price' => $salePrice,
            'stock' => $stock,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }
}
