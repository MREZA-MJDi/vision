<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_redirect_guests_to_the_package_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('commerce-admin.login'));

        $this->get('/admin/login')->assertOk();
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

        $this->actingAs($admin)
            ->patch(route('commerce-admin.orders.status', $order->getKey()), [
                'status' => 'cancelled',
            ])
            ->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, (int) $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->getKey(),
            'type' => 'adjustment',
            'quantity' => 2,
            'stock_after' => 5,
            'reference_id' => $order->getKey(),
        ]);

        $movementCount = InventoryMovement::query()->count();

        $this->patch(route('commerce-admin.orders.status', $order->getKey()), [
            'status' => 'cancelled',
        ])->assertRedirect(route('commerce-admin.orders.show', $order->getKey()));

        $this->assertSame(5, (int) $variant->fresh()->stock);
        $this->assertSame($movementCount, InventoryMovement::query()->count());
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
}
