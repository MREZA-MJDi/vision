<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_database_metrics_and_aggregates_the_twelve_month_chart_once(): void
    {
        $admin = User::query()->create([
            'name' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $customer = User::query()->create([
            'name' => 'Dashboard Customer',
            'email' => 'dashboard-customer@example.test',
            'password' => 'customer-password-123',
        ]);

        $category = Category::query()->create([
            'name' => 'Dashboard Category',
            'slug' => 'dashboard-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'name' => 'Dashboard Product',
            'slug' => 'dashboard-product',
            'is_active' => true,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'DASHBOARD-STOCK-01',
            'size' => 'M',
            'price' => 19.90,
            'stock' => 1,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        Order::query()->create([
            'user_id' => $customer->getKey(),
            'order_number' => 'VS-DASHBOARD-0001',
            'customer_name' => $customer->name,
            'customer_phone' => '555-0101',
            'customer_email' => $customer->email,
            'shipping_address' => '۱ خیابان تست',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash_on_delivery',
            'subtotal' => 19.90,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 19.90,
            'placed_at' => now(),
        ]);

        Order::query()->create([
            'user_id' => $customer->getKey(),
            'order_number' => 'VS-DASHBOARD-0002',
            'customer_name' => $customer->name,
            'customer_phone' => '555-0101',
            'customer_email' => $customer->email,
            'shipping_address' => '۱ خیابان تست',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'manual',
            'subtotal' => 45,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 45,
            'placed_at' => now(),
        ]);

        $groupedSeriesQueries = [];
        $dashboardMetricQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$groupedSeriesQueries, &$dashboardMetricQueries): void {
            if (str_contains($query->sql, 'month_bucket')) {
                $groupedSeriesQueries[] = $query->sql;
            }
            if (str_contains($query->sql, 'pending_orders') && str_contains($query->sql, 'paid_revenue')) {
                $dashboardMetricQueries[] = $query->sql;
            }
        });

        $this->actingAs($admin)
            ->get(route('commerce-admin.dashboard'))
            ->assertOk()
            ->assertSee('درآمد پرداخت‌شده')
            ->assertSee('آخرین سفارش‌ها')
            ->assertSee('45 تومان');

        $this->assertCount(1, $groupedSeriesQueries, 'The 12-month chart should use one grouped aggregation query.');
        $this->assertCount(1, $dashboardMetricQueries, 'The order dashboard KPIs should share one aggregation query.');
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->getKey(),
            'stock' => 1,
        ]);
    }

    public function test_dashboard_and_analytics_render_empty_states_without_fabricated_metrics(): void
    {
        $admin = User::query()->create([
            'name' => 'Empty Dashboard Admin',
            'email' => 'empty-dashboard@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('commerce-admin.dashboard'))
            ->assertOk()
            ->assertSee('0 تومان')
            ->assertSee('هنوز سفارشی ثبت نشده است.');

        $this->get(route('commerce-admin.analytics'))
            ->assertOk()
            ->assertSee('0 تومان');
    }
}
