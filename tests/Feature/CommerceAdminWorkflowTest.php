<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_redirect_guests_to_the_shared_storefront_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('commerce-admin.login'));

        $this->get('/admin/login')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_non_admin_customers_cannot_open_the_admin_panel(): void
    {
        $customer = User::query()->create([
            'name' => 'Ordinary Customer',
            'email' => 'ordinary@example.test',
            'password' => 'customer-password-123',
        ]);

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_cancellation_restores_stock_only_once_even_when_repeated(): void
    {
        $admin = User::query()->create([
            'name' => 'Commerce Admin',
            'email' => 'admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $category = Category::query()->create([
            'name' => 'Stock Category',
            'slug' => 'stock-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Stock Test Product',
            'slug' => 'stock-test-product',
            'is_active' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'STOCK-TEST-0001',
            'size' => 'M',
            'price' => 25.00,
            'stock' => 3,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'order_number' => 'VS-STOCK-TEST-0001',
            'customer_name' => 'Test Buyer',
            'customer_phone' => '555-0199',
            'customer_email' => 'buyer@example.test',
            'shipping_address' => '1 Test Road',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 50.00,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 50.00,
            'placed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'product_name' => $product->name,
            'variant_name' => $variant->display_name,
            'sku' => $variant->sku,
            'unit_price' => 25.00,
            'quantity' => 2,
            'line_total' => 50.00,
            'tax_amount' => 0,
        ]);

        InventoryMovement::query()->create([
            'product_variant_id' => $variant->getKey(),
            'type' => 'sale',
            'quantity' => -2,
            'stock_after' => 3,
            'reference_type' => $order->getMorphClass(),
            'reference_id' => $order->getKey(),
            'note' => 'Initial reservation for test order',
            'created_by' => $admin->getKey(),
        ]);

        $movementCount = InventoryMovement::query()->count();

        // The variant can be archived from the live catalog while an order
        // still references it. Cancellation must restore its archived SKU.
        $variant->delete();

        $this->actingAs($admin)
            ->patch(route('commerce-admin.orders.status', $order->getKey()), [
                'status' => 'cancelled',
            ])
            ->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $this->assertSame('cancelled', $order->fresh()->status);
        $archivedVariant = ProductVariant::withTrashed()->findOrFail($variant->getKey());
        $this->assertSame(5, (int) $archivedVariant->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->getKey(),
            'type' => 'adjustment',
            'quantity' => 2,
            'stock_after' => 5,
            'reference_id' => $order->getKey(),
        ]);
        $this->assertSame($movementCount + 1, InventoryMovement::query()->count());

        // Repeating a terminal transition must never restore stock twice.
        $this->patch(route('commerce-admin.orders.status', $order->getKey()), [
            'status' => 'cancelled',
        ])->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $this->assertSame(5, (int) ProductVariant::withTrashed()->findOrFail($variant->getKey())->stock);
        $this->assertSame($movementCount + 1, InventoryMovement::query()->count());
    }

    public function test_order_can_progress_through_out_for_delivery_and_records_timestamps(): void
    {
        $admin = User::query()->create([
            'name' => 'Delivery Admin',
            'email' => 'delivery-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $order = Order::query()->create([
            'order_number' => 'VS-DELIVERY-MILESTONE-0001',
            'customer_name' => 'Delivery Test Buyer',
            'shipping_address' => '4 Test Road',
            'status' => 'shipped',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 10,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 10,
            'placed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('commerce-admin.orders.status', $order->getKey()), [
                'status' => 'out_for_delivery',
            ])
            ->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $order->refresh();
        $this->assertSame('out_for_delivery', $order->status);
        $this->assertNotNull($order->out_for_delivery_at);

        $this->patch(route('commerce-admin.orders.status', $order->getKey()), [
            'status' => 'delivered',
        ])->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->out_for_delivery_at);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_order_state_machine_rejects_skipping_fulfillment_steps(): void
    {
        $admin = User::query()->create([
            'name' => 'Commerce Admin',
            'email' => 'admin-flow@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $order = Order::query()->create([
            'order_number' => 'VS-STATE-TEST-0001',
            'customer_name' => 'Test Buyer',
            'customer_phone' => '555-0198',
            'shipping_address' => '2 Test Road',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 10,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 10,
            'placed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('commerce-admin.orders.show', $order->getKey()))
            ->patch(route('commerce-admin.orders.status', $order->getKey()), [
                'status' => 'delivered',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_manual_payment_updates_are_persisted_to_order_and_payment_records(): void
    {
        $admin = User::query()->create([
            'name' => 'Payment Admin',
            'email' => 'payment-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $order = Order::query()->create([
            'order_number' => 'VS-PAYMENT-TEST-0001',
            'customer_name' => 'Test Buyer',
            'customer_phone' => '555-0188',
            'shipping_address' => '3 Test Road',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 42.50,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 42.50,
            'placed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('commerce-admin.orders.payment', $order->getKey()), [
                'payment_status' => 'paid',
            ])
            ->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);

        $payment = Payment::query()->where('order_id', $order->getKey())->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('42.50', $payment->amount);
        $this->assertNotNull($payment->paid_at);

        $this->patch(route('commerce-admin.orders.payment', $order->getKey()), [
            'payment_status' => 'refunded',
        ])->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('refunded', $payment->fresh()->status);
    }


    public function test_product_variants_do_not_expose_or_require_barcode(): void
    {
        $this->assertFalse(Schema::hasColumn('product_variants', 'barcode'));

        $admin = User::query()->create([
            'name' => 'No Barcode Admin',
            'email' => 'no-barcode-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('commerce-admin.products.create'))
            ->assertOk()
            ->assertDontSee('barcode')
            ->assertDontSee('بارکد');
    }

    public function test_admin_can_create_a_product_without_manual_sku_and_stock_is_audited(): void
    {
        $admin = User::query()->create([
            'name' => 'Catalog Admin',
            'email' => 'catalog-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->post(route('commerce-admin.products.store'), [
                'name' => 'Cropped Jacket',
                'slug' => '',
                'status' => 'active',
                'is_taxable' => '1',
                'attributes' => [
                    ['key' => 'Material', 'value' => 'Cotton'],
                    ['key' => 'Weight', 'value' => '250 g'],
                ],
                'variants' => [
                    [
                        'sku' => '',
                        'size' => 'M',
                        'color' => 'Black',
                        'color_code' => '#000000',
                        'price' => '۷۹,۵۰۰',
                        'sale_price' => '',
                        'stock' => '4',
                        'low_stock_threshold' => '2',
                        'is_active' => '1',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('name', 'Cropped Jacket')->firstOrFail();
        $variant = ProductVariant::query()->where('product_id', $product->getKey())->firstOrFail();

        $this->assertSame('cropped-jacket', $product->slug);
        $this->assertStringStartsWith('VIS-', $variant->sku);
        $this->assertSame(['Material' => 'Cotton', 'Weight' => '250 g'], $product->attributes);
        $this->assertEquals(79500, (float) $variant->price);
        $this->assertSame('Cropped Jacket', $product->meta_title);
        $this->assertStringContainsString('Cropped Jacket', (string) $product->meta_description);
        $this->assertSame(4, (int) $variant->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->getKey(),
            'type' => 'adjustment',
            'quantity' => 4,
            'stock_after' => 4,
            'reference_id' => $product->getKey(),
            'note' => 'Initial stock set from the product editor.',
        ]);
    }


    public function test_admin_manual_order_creates_payment_token_and_inventory_audit_atomically(): void
    {
        config([
            'commerce-admin.tax_rate' => 0.10,
            'commerce-admin.shipping_flat_rate' => 0,
        ]);

        $admin = User::query()->create([
            'name' => 'Manual Order Admin',
            'email' => 'manual-order-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $category = Category::query()->create([
            'name' => 'Manual Order Category',
            'slug' => 'manual-order-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Manual Order Product',
            'slug' => 'manual-order-product',
            'is_active' => true,
            'is_taxable' => true,
        ]);

        $firstVariant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'MANUAL-ORDER-001',
            'size' => 'M',
            'price' => 10.00,
            'stock' => 5,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $secondVariant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'MANUAL-ORDER-002',
            'size' => 'L',
            'price' => 15.00,
            'stock' => 3,
            'low_stock_threshold' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('commerce-admin.orders.store'), [
                'customer_name' => 'Manual Order Customer',
                'customer_email' => 'buyer@example.test',
                'customer_phone' => '555-0144',
                'shipping_address' => '1 Commerce Street',
                'shipping_province' => 'Test Province',
                'shipping_city' => 'Test City',
                'postal_code' => '12345',
                'payment_method' => 'bank_transfer',
                'shipping_cost' => '5.00',
                'items' => [
                    ['variant_id' => $firstVariant->getKey(), 'quantity' => 2],
                    ['variant_id' => $secondVariant->getKey(), 'quantity' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();

        $order = Order::query()->where('customer_email', 'buyer@example.test')->firstOrFail();

        $this->assertNotEmpty($order->public_token);
        $this->assertSame('35.00', $order->subtotal);
        $this->assertSame('3.50', $order->tax);
        $this->assertSame('5.00', $order->shipping_cost);
        $this->assertSame('43.50', $order->total);
        $this->assertSame(2, $order->items()->count());

        $this->assertSame(3, (int) $firstVariant->fresh()->stock);
        $this->assertSame(2, (int) $secondVariant->fresh()->stock);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $firstVariant->getKey(),
            'type' => 'sale',
            'quantity' => -2,
            'stock_after' => 3,
            'reference_id' => $order->getKey(),
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $secondVariant->getKey(),
            'type' => 'sale',
            'quantity' => -1,
            'stock_after' => 2,
            'reference_id' => $order->getKey(),
        ]);

        $payment = Payment::query()->where('order_id', $order->getKey())->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('43.50', $payment->amount);
        $this->assertSame('manual', $payment->gateway);
        $this->assertSame('admin-order:' . $order->order_number, $payment->idempotency_key);
    }

}
