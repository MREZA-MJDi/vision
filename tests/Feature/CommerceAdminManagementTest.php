<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_commerce_management_pages_are_protected_for_guests(): void
    {
        foreach ([
            '/admin/categories',
            '/admin/brands',
            '/admin/customers',
            '/admin/payments',
            '/admin/inventory',
        ] as $path) {
            $this->get($path)->assertRedirect(route('commerce-admin.login'));
        }
    }

    public function test_admin_can_create_categories_and_prevent_parent_cycles(): void
    {
        $admin = $this->makeAdmin('categories-admin@example.test');

        $this->actingAs($admin)
            ->post(route('commerce-admin.categories.store'), [
                'name' => 'پوشاک',
                'slug' => '',
                'status' => 'active',
                'sort_order' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('commerce-admin.categories.index'));

        $parent = Category::query()->where('name', 'پوشاک')->firstOrFail();
        $this->assertSame('پوشاک', $parent->name);
        $this->assertNotSame('', $parent->slug);
        $this->assertDatabaseHas('categories', ['slug' => $parent->slug]);

        $child = Category::query()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'پیراهن',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        $this->from(route('commerce-admin.categories.edit', $parent->getKey()))
            ->put(route('commerce-admin.categories.update', $parent->getKey()), [
                'name' => $parent->name,
                'slug' => $parent->slug,
                'parent_id' => $child->getKey(),
                'status' => 'active',
                'sort_order' => 1,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_category_delete_is_soft_archived_when_products_still_use_it(): void
    {
        $admin = $this->makeAdmin('category-archive-admin@example.test');
        $category = Category::query()->create([
            'name' => 'Archive Category',
            'slug' => 'archive-category',
            'is_active' => true,
        ]);
        $product = $this->makeProduct('archive-category-product', $category->getKey());

        $this->actingAs($admin)
            ->delete(route('commerce-admin.categories.destroy', $category->getKey()))
            ->assertRedirect(route('commerce-admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->getKey(),
            'deleted_at' => null,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'category_id' => $category->getKey(),
        ]);
    }

    public function test_brand_delete_deactivates_a_brand_that_has_products(): void
    {
        $admin = $this->makeAdmin('brand-archive-admin@example.test');
        $brand = Brand::query()->create([
            'name' => 'Archive Brand',
            'slug' => 'archive-brand',
            'is_active' => true,
        ]);
        $product = $this->makeProduct('archive-brand-product', null, $brand->getKey());

        $this->actingAs($admin)
            ->delete(route('commerce-admin.brands.destroy', $brand->getKey()))
            ->assertRedirect(route('commerce-admin.brands.index'));

        $this->assertDatabaseHas('brands', [
            'id' => $brand->getKey(),
            'deleted_at' => null,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'brand_id' => $brand->getKey(),
        ]);
    }

    public function test_customer_directory_and_profile_show_real_customer_orders_but_not_admins(): void
    {
        $admin = $this->makeAdmin('customers-admin@example.test');
        $customer = User::query()->create([
            'name' => 'Customer With History',
            'email' => 'history-customer@example.test',
            'password' => 'customer-password-123',
            'is_admin' => false,
        ]);
        $order = $this->makeOrder($customer, 'VS-CUSTOMER-HISTORY-01');

        $this->actingAs($admin)
            ->get(route('commerce-admin.customers.index'))
            ->assertOk()
            ->assertSee('Customer With History')
            ->assertSee('history-customer@example.test')
            ->assertDontSee('customers-admin@example.test');

        $this->get(route('commerce-admin.customers.show', $customer->getKey()))
            ->assertOk()
            ->assertSee('سابقهٔ سفارش‌ها')
            ->assertSee($order->order_number);
    }

    public function test_payment_dashboard_lists_real_payments_with_order_context(): void
    {
        $admin = $this->makeAdmin('payments-admin@example.test');
        $order = $this->makeOrder(null, 'VS-REAL-PAYMENT-01');

        Payment::query()->create([
            'order_id' => $order->getKey(),
            'gateway' => 'manual',
            'idempotency_key' => 'test-payment-' . $order->getKey(),
            'amount' => 54.25,
            'status' => 'paid',
            'reference_number' => 'PAY-REAL-101',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('commerce-admin.payments.index', ['status' => 'paid']))
            ->assertOk()
            ->assertSee('VS-REAL-PAYMENT-01')
            ->assertSee('PAY-REAL-101')
            ->assertSee('54.25');
    }

    public function test_stock_adjustment_updates_stock_and_writes_an_audit_movement(): void
    {
        $admin = $this->makeAdmin('inventory-admin@example.test');
        $category = Category::query()->create([
            'name' => 'Inventory Category',
            'slug' => 'inventory-category',
            'is_active' => true,
        ]);
        $product = $this->makeProduct('inventory-product', $category->getKey());
        $variant = $this->makeVariant($product, 'INV-AUDIT-01', 5);

        $this->actingAs($admin)
            ->post(route('commerce-admin.inventory.adjust', $variant->getKey()), [
                'adjustment' => 3,
                'note' => 'ثبت ورودی انبار',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('commerce-admin.inventory.index'));

        $this->assertSame(8, (int) $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->getKey(),
            'type' => 'adjustment',
            'quantity' => 3,
            'stock_after' => 8,
            'note' => 'ثبت ورودی انبار',
            'created_by' => $admin->getKey(),
        ]);
    }

    public function test_stock_adjustment_rejects_negative_inventory_without_audit_side_effects(): void
    {
        $admin = $this->makeAdmin('inventory-negative-admin@example.test');
        $product = $this->makeProduct('inventory-negative-product');
        $variant = $this->makeVariant($product, 'INV-AUDIT-02', 2);
        $movementCount = InventoryMovement::query()->count();

        $this->actingAs($admin)
            ->from(route('commerce-admin.inventory.index'))
            ->post(route('commerce-admin.inventory.adjust', $variant->getKey()), [
                'adjustment' => -3,
                'note' => 'کسری غیرمجاز',
            ])
            ->assertSessionHasErrors('adjustment');

        $this->assertSame(2, (int) $variant->fresh()->stock);
        $this->assertSame($movementCount, InventoryMovement::query()->count());
    }

    private function makeAdmin(string $email): User
    {
        $admin = User::query()->create([
            'name' => 'Store Administrator',
            'email' => $email,
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function makeProduct(string $slug, ?int $categoryId = null, ?int $brandId = null): Product
    {
        return Product::query()->create([
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'name' => str_replace('-', ' ', ucfirst($slug)),
            'slug' => $slug,
            'is_active' => true,
            'is_taxable' => false,
        ]);
    }

    private function makeVariant(Product $product, string $sku, int $stock): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => $sku,
            'size' => 'M',
            'price' => 20,
            'stock' => $stock,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);
    }

    private function makeOrder(?User $customer, string $number): Order
    {
        return Order::query()->create([
            'user_id' => $customer?->getKey(),
            'order_number' => $number,
            'customer_name' => $customer?->name ?? 'Guest Buyer',
            'customer_phone' => '555-0110',
            'customer_email' => $customer?->email,
            'shipping_address' => 'آدرس تست',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'manual',
            'subtotal' => 54.25,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 54.25,
            'placed_at' => now(),
        ]);
    }
}
