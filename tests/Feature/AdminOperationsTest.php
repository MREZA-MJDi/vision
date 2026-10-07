<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Vision Admin',
            'email' => 'admin@example.com',
            'phone' => '09120000001',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '09120000002',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Shoes',
            'slug' => 'shoes-' . uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function brand(array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'Vision Brand',
            'slug' => 'vision-brand-' . uniqid(),
            'is_active' => true,
        ], $overrides));
    }

    private function product(Category $category, ?Brand $brand = null, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => 'Classic Sneaker',
            'slug' => 'classic-sneaker-' . uniqid(),
            'short_description' => 'A test product',
            'description' => 'A test product description',
            'is_active' => true,
            'is_featured' => false,
            'is_hero' => false,
            'sort_order' => 0,
        ], $overrides));
    }

    private function variant(Product $product, array $overrides = []): ProductVariant
    {
        return ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'sku' => 'SKU-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'size' => '42',
            'color' => 'Black',
            'color_code' => '#000000',
            'price' => 250000,
            'sale_price' => null,
            'stock' => 10,
            'low_stock_threshold' => 2,
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function order(User $customer, Product $product, ProductVariant $variant, array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'VN-' . strtoupper(substr(md5(uniqid()), 0, 10)),
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipping_address' => 'Test address',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'online',
            'subtotal' => 500000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 500000,
            'placed_at' => now(),
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->display_name,
            'sku' => $variant->sku,
            'unit_price' => 250000,
            'quantity' => 2,
            'line_total' => 500000,
        ]);

        return $order->fresh('items');
    }

    public function test_guest_and_customer_cannot_access_admin_but_admin_can(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->customer())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_can_login_and_logout(): void
    {
        $admin = $this->admin();

        $this->post(route('login.store'), [
            'phone' => $admin->phone,
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_category_crud_works(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Women Shoes',
            'slug' => 'women-shoes',
            'description' => 'Shoes',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $category = Category::where('slug', 'women-shoes')->firstOrFail();

        $response->assertRedirect(route('admin.categories.edit', $category));

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Women Footwear',
            'slug' => 'women-footwear',
            'description' => 'Updated',
            'sort_order' => 2,
            'is_active' => 1,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Women Footwear',
            'slug' => 'women-footwear',
        ]);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_brand_crud_works(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.brands.store'), [
            'name' => 'Vision',
            'slug' => 'vision',
            'description' => 'Fashion brand',
            'is_active' => 1,
        ]);

        $brand = Brand::where('slug', 'vision')->firstOrFail();

        $response->assertRedirect(route('admin.brands.edit', $brand));

        $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
            'name' => 'Vision Paris',
            'slug' => 'vision-paris',
            'description' => 'Updated brand',
            'is_active' => 1,
        ])->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Vision Paris',
            'slug' => 'vision-paris',
        ]);

        $this->actingAs($admin)->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'));

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    }

    public function test_product_and_variant_crud_works_without_wholesale_fields(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $brand = $this->brand();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Vision Runner',
            'slug' => 'vision-runner',
            'short_description' => 'Runner',
            'description' => 'Comfortable runner',
            'sku' => 'VR-001',
            'size' => '41',
            'color' => 'Black',
            'color_code' => '#000000',
            'price' => '350000',
            'sale_price' => '300000',
            'stock' => 8,
            'low_stock_threshold' => 2,
            'is_active' => 1,
            'is_featured' => 1,
            'sort_order' => 1,
        ])->assertSessionHasNoErrors();

        $product = Product::where('slug', 'vision-runner')->firstOrFail();
        $mainVariant = $product->variants()->firstOrFail();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Vision Runner',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $mainVariant->id,
            'sku' => 'VR-001',
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Vision Runner Updated',
            'slug' => 'vision-runner-updated',
            'short_description' => 'Updated',
            'description' => 'Updated description',
            'sku' => 'VR-001',
            'size' => '42',
            'color' => 'White',
            'color_code' => '#FFFFFF',
            'price' => '375000',
            'sale_price' => '',
            'stock' => 9,
            'low_stock_threshold' => 2,
            'is_active' => 1,
            'is_featured' => 1,
            'sort_order' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Vision Runner Updated',
        ]);

        $this->actingAs($admin)->patch(route('admin.products.hero.toggle', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_hero' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), [
            'product_id' => $product->id,
            'sku' => 'VR-002',
            'size' => '43',
            'color' => 'White',
            'color_code' => '#FFFFFF',
            'price' => 400000,
            'sale_price' => null,
            'stock' => 6,
            'low_stock_threshold' => 1,
            'is_active' => 1,
            'sort_order' => 2,
        ])->assertRedirect(route('admin.products.variants.index', $product));

        $variant = ProductVariant::where('sku', 'VR-002')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.products.variants.update', [$product, $variant]), [
            'product_id' => $product->id,
            'sku' => 'VR-002-B',
            'size' => '44',
            'color' => 'Gray',
            'color_code' => '#888888',
            'price' => 420000,
            'sale_price' => null,
            'stock' => 6,
            'low_stock_threshold' => 1,
            'is_active' => 1,
            'sort_order' => 3,
        ])->assertRedirect(route('admin.products.variants.index', $product));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'VR-002-B',
        ]);

        $this->actingAs($admin)->delete(route('admin.products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('admin.products.variants.index', $product));

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    public function test_inventory_adjustment_updates_stock_and_audit_row(): void
    {
        $admin = $this->admin();
        $product = $this->product($this->category());
        $variant = $this->variant($product, ['stock' => 10]);

        $this->actingAs($admin)->post(route('admin.inventory.store'), [
            'product_variant_id' => $variant->id,
            'type' => 'purchase',
            'quantity' => 5,
            'note' => 'Restock',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock' => 15,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'purchase',
            'quantity' => 5,
            'stock_after' => 15,
            'created_by' => $admin->id,
        ]);
    }

    public function test_order_status_changes_are_transactional_and_restore_inventory(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $product = $this->product($this->category());
        $variant = $this->variant($product, ['stock' => 8]);
        $order = $this->order($customer, $product, $variant);

        $this->actingAs($admin)->put(route('admin.orders.update', $order), [
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'customer_note' => 'Confirmed',
            'tracking_code' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($admin)->put(route('admin.orders.update', $order), [
            'status' => 'cancelled',
            'payment_status' => 'pending',
            'customer_note' => 'Cancelled',
            'tracking_code' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock' => 10,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'quantity' => 2,
            'stock_after' => 10,
            'reference_id' => $order->id,
        ]);

        $this->actingAs($admin)->put(route('admin.orders.update', $order), [
            'status' => 'delivered',
            'payment_status' => 'pending',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_order_can_be_marked_failed_and_payment_is_persisted(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $product = $this->product($this->category());
        $variant = $this->variant($product);
        $order = $this->order($customer, $product, $variant);

        $this->actingAs($admin)->put(route('admin.orders.update', $order), [
            'status' => 'confirmed',
            'payment_status' => 'failed',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'failed',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }

    public function test_financial_transaction_is_created(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.accounting.store'), [
            'type' => 'expense',
            'category' => 'rent',
            'amount' => 1500000,
            'description' => 'Monthly rent',
            'transaction_date' => '2026-10-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('financial_transactions', [
            'type' => 'expense',
            'category' => 'rent',
            'amount' => 1500000,
            'created_by' => $admin->id,
        ]);
    }

    public function test_customer_listing_profile_and_contact_operations_work(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->get(route('admin.customers.index', ['q' => 'Test Customer']))
            ->assertOk()
            ->assertSee('Test Customer');

        $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'name' => 'Vision Admin Updated',
            'email' => 'admin-updated@example.com',
            'phone' => '09120000003',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Vision Admin Updated',
            'email' => 'admin-updated@example.com',
        ]);

        $message = ContactMessage::create([
            'name' => 'Visitor',
            'email' => 'visitor@example.com',
            'phone' => '09123334444',
            'subject' => 'Hello',
            'message' => 'Test message',
            'status' => ContactMessage::STATUS_NEW,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact.show', $message))
            ->assertOk();

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'status' => ContactMessage::STATUS_READ,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.contact.status', $message), [
                'status' => ContactMessage::STATUS_REPLIED,
            ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'status' => ContactMessage::STATUS_REPLIED,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
        ]);
    }

    public function test_product_media_upload_reorder_update_and_delete_work(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $product = $this->product($this->category());

        $first = $this->actingAs($admin)->post(
            route('admin.products.media.store', $product),
            ['images' => [\Illuminate\Http\UploadedFile::fake()->image('first.jpg', 600, 600)]]
        );

        $first->assertRedirect();

        $second = \App\Models\Media::query()->where('mediable_id', $product->id)->latest('id')->firstOrFail();

        $this->actingAs($admin)->post(
            route('admin.products.media.store', $product),
            ['images' => [\Illuminate\Http\UploadedFile::fake()->image('second.jpg', 600, 600)]]
        )->assertRedirect();

        $media = Media::where('mediable_id', $product->id)->orderBy('id')->get();

        $this->actingAs($admin)->postJson(
            route('admin.products.media.reorder', $product),
            ['media' => $media->pluck('id')->reverse()->values()->all()]
        )->assertOk();

        $this->actingAs($admin)->patch(
            route('admin.products.media.update', [$product, $second]),
            ['alt_text' => 'Updated image']
        )->assertRedirect();

        $this->assertDatabaseHas('media', [
            'id' => $second->id,
            'alt_text' => 'Updated image',
        ]);

        $this->actingAs($admin)->delete(
            route('admin.products.media.destroy', [$product, $second])
        )->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $second->id]);
    }

    public function test_cheque_and_wholesale_subsystems_are_removed(): void
    {
        $this->assertFalse(class_exists(\App\Models\ChequePayment::class));
        $this->assertFalse(class_exists(\App\Models\ChequePermission::class));
        $this->assertFalse(class_exists(\App\Models\WholesalePack::class));
        $this->assertFalse(class_exists(\App\Models\WholesalePackItem::class));
        $this->assertFalse(class_exists(\App\Models\WholesaleProfile::class));
        $this->assertFalse(class_exists(\App\Services\ChequePaymentService::class));
        $this->assertFalse(in_array('wholesale_price', (new ProductVariant())->getFillable(), true));
        $this->assertFalse(in_array('order_type', (new Order())->getFillable(), true));
        $this->assertFalse(Route::has('admin.wholesale.index'));
        $this->assertFalse(Route::has('admin.wholesale-packs.index'));
        $this->assertFalse(Route::has('admin.cheques.index'));

        $this->get('/wholesale')->assertNotFound();
        $this->get('/cheques')->assertNotFound();
        $this->get('/admin/wholesale')->assertNotFound();
        $this->get('/admin/wholesale-packs')->assertNotFound();
        $this->get('/admin/cheques')->assertNotFound();
    }
}
