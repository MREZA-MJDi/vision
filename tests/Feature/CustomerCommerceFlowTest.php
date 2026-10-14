<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_and_customer_auth_screens_render_without_catalog_context(): void
    {
        $this->get('/cart')->assertOk()->assertSee('Nothing in your bag yet.');
        $this->get('/login')->assertOk()->assertSee('Sign in');
        $this->get('/register')->assertOk()->assertSee('Create account');
        $this->get('/checkout')->assertRedirect(route('cart.index'));
    }

    public function test_customer_can_add_a_real_variant_and_place_an_order_once(): void
    {
        config([
            'commerce-admin.tax_rate' => 0.10,
            'commerce-admin.shipping_flat_rate' => 5.00,
        ]);

        [$product, $variant] = $this->createProduct(
            price: 12.50,
            salePrice: 10.00,
            stock: 3,
            taxable: true
        );

        // Use a signed-in customer's persistent account cart here. Guest-cart
        // creation is covered by the cart route itself; this feature test then
        // exercises the complete checkout transaction against a stable cart
        // identity across multiple HTTP requests.
        $buyer = User::query()->create([
            'name' => 'Checkout Customer',
            'email' => 'checkout-customer@example.test',
            'password' => 'customer-password-123',
            'is_admin' => false,
        ]);
        $this->actingAs($buyer);

        $this->postJson('/cart/items', [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])->assertCreated()
            ->assertJsonPath('count', 1);

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
        ]);

        $payload = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'buyer@example.test',
            'customer_phone' => '555-0101',
            'shipping_address' => '123 Test Street',
            'shipping_city' => 'Test City',
            'shipping_province' => 'Test Province',
            'postal_code' => '12345',
            'payment_method' => 'cash_on_delivery',
        ];

        $response = $this->post('/checkout', $payload);
        $response->assertSessionHasNoErrors();
        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('checkout.complete', ['token' => $order->public_token]));
        $this->assertNotEmpty($order->public_token);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('10.00', $order->subtotal);
        $this->assertSame('1.00', $order->tax);
        $this->assertSame('5.00', $order->shipping_cost);
        $this->assertSame('16.00', $order->total);

        $item = OrderItem::query()->where('order_id', $order->getKey())->firstOrFail();
        $this->assertSame('10.00', $item->unit_price);
        $this->assertSame('10.00', $item->line_total);
        $this->assertSame('1.00', $item->tax_amount);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->getKey(),
            'stock' => 2,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->getKey(),
            'type' => 'sale',
            'quantity' => -1,
            'stock_after' => 2,
            'reference_id' => $order->getKey(),
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'status' => 'pending',
            'amount' => '16.00',
        ]);
        $this->assertSame(0, CartItem::query()->count());

        $this->get(route('checkout.complete', ['token' => $order->public_token]))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('16 تومان');

        // A duplicate form submission cannot create another order after the
        // cart has been consumed, nor can it reserve the same stock twice.
        $this->from('/checkout')->post('/checkout', $payload);
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(2, (int) $variant->fresh()->stock);
        $this->assertSame(1, InventoryMovement::query()->where('type', 'sale')->count());
    }

    public function test_customer_cannot_add_an_inactive_or_overstocked_variant(): void
    {
        [, $variant] = $this->createProduct(price: 25.00, salePrice: null, stock: 1);

        $this->postJson('/cart/items', [
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $variant->update(['is_active' => false]);
        $this->postJson('/cart/items', [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');

        $this->assertSame(0, CartItem::query()->count());
        $this->assertSame(0, Order::query()->count());
    }

    public function test_customer_registration_creates_non_admin_account_and_order_history_is_private(): void
    {
        $this->post('/register', [
            'name' => 'Registered Customer',
            'email' => 'registered@example.test',
            'phone' => '555-0133',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
            'terms' => '1',
        ])->assertRedirect(route('account.show'));

        $customer = User::query()->where('email', 'registered@example.test')->firstOrFail();
        $this->assertFalse((bool) $customer->is_admin);
        $this->assertAuthenticatedAs($customer);
        $this->get('/account')->assertOk()->assertSee('My account');

        $otherCustomer = User::query()->create([
            'name' => 'Another Customer',
            'email' => 'another@example.test',
            'phone' => '555-0134',
            'password' => 'another-password-123',
        ]);

        $foreignOrder = Order::query()->create([
            'user_id' => $otherCustomer->getKey(),
            'order_number' => 'VS-PRIVATE-0001',
            'customer_name' => 'Another Customer',
            'customer_phone' => '555-0134',
            'customer_email' => 'another@example.test',
            'shipping_address' => 'Private address',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 20,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 20,
            'placed_at' => now(),
        ]);

        $this->get(route('account.orders.show', $foreignOrder->getKey()))
            ->assertNotFound();
    }

    private function createProduct(
        float $price,
        ?float $salePrice,
        int $stock,
        bool $taxable = false
    ): array {
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
            'is_taxable' => $taxable,
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

    public function test_guest_can_checkout_from_the_same_session_backed_cart(): void
    {
        config([
            'commerce-admin.tax_rate' => 0.10,
            'commerce-admin.shipping_flat_rate' => 2.00,
        ]);

        [, $variant] = $this->createProduct(
            price: 25.00,
            salePrice: null,
            stock: 2,
            taxable: true
        );

        $this->postJson('/cart/items', [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])->assertCreated()->assertJsonPath('count', 1);

        $guestCart = Cart::query()
            ->whereNull('user_id')
            ->whereNotNull('session_id')
            ->orderByDesc('id')
            ->firstOrFail();

        $payload = [
            'customer_name' => 'Guest Checkout',
            'customer_email' => 'guest-checkout@example.test',
            'customer_phone' => '555-0155',
            'shipping_address' => '45 Guest Street',
            'shipping_city' => 'Test City',
            'shipping_province' => 'Test Province',
            'postal_code' => '54321',
            'payment_method' => 'cash_on_delivery',
        ];

        // The test client does not automatically reuse the encrypted session
        // cookie returned by a previous independent request. Send the exact
        // session id produced by the cart request to simulate a returning
        // same-browser customer.
        $response = $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie(config('session.cookie'), $guestCart->session_id)
            ->post('/checkout', $payload);

        $response->assertSessionHasNoErrors();
        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('checkout.complete', ['token' => $order->public_token]));
        $this->assertNull($order->user_id);
        $this->assertSame('25.00', $order->subtotal);
        $this->assertSame('2.50', $order->tax);
        $this->assertSame('2.00', $order->shipping_cost);
        $this->assertSame('29.50', $order->total);
        $this->assertSame(0, CartItem::query()->count(), 'A successful guest checkout must consume the guest cart.');
        $this->assertSame(1, InventoryMovement::query()->where('type', 'sale')->count());
        $this->assertSame(1, (int) $variant->fresh()->stock);
    }

}
