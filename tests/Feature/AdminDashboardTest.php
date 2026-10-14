<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
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

        // Add volume before the paid order: query count should remain constant
        // regardless of how many recent records need rendering.
        for ($number = 3; $number <= 27; $number++) {
            Order::query()->create([
                'user_id' => $customer->getKey(),
                'order_number' => sprintf('VS-DASHBOARD-BULK-%04d', $number),
                'customer_name' => $customer->name,
                'customer_phone' => '555-0101',
                'customer_email' => $customer->email,
                'shipping_address' => '۱ خیابان تست',
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'cash_on_delivery',
                'subtotal' => 10,
                'discount' => 0,
                'shipping_cost' => 0,
                'tax' => 0,
                'total' => 10,
                'placed_at' => now()->subDays($number),
            ]);
        }

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
        $dashboardSelectQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$groupedSeriesQueries, &$dashboardMetricQueries, &$dashboardSelectQueries): void {
            $sql = strtolower(ltrim($query->sql));
            if (str_starts_with($sql, 'select')) {
                $dashboardSelectQueries[] = $query->sql;
            }
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
        $this->assertLessThanOrEqual(
            10,
            count($dashboardSelectQueries),
            'Dashboard SELECT query count must stay bounded when the number of recent orders grows.'
        );
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->getKey(),
            'stock' => 1,
        ]);
    }

    public function test_analytics_optimized_aggregates_keep_returning_customer_and_review_metrics_correct(): void
    {
        $admin = User::query()->create([
            'name' => 'Analytics Admin',
            'email' => 'analytics-admin@example.test',
            'password' => 'admin-password-123',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $customer = User::query()->create([
            'name' => 'Repeat Customer',
            'email' => 'repeat-customer@example.test',
            'password' => 'customer-password-123',
        ]);

        $orders = [
            ['number' => 'ANALYTICS-USER-01', 'user_id' => $customer->getKey(), 'email' => $customer->email],
            ['number' => 'ANALYTICS-USER-02', 'user_id' => $customer->getKey(), 'email' => $customer->email],
            ['number' => 'ANALYTICS-GUEST-01', 'user_id' => null, 'email' => 'Repeat@Example.test'],
            ['number' => 'ANALYTICS-GUEST-02', 'user_id' => null, 'email' => 'repeat@example.test'],
            ['number' => 'ANALYTICS-GUEST-03', 'user_id' => null, 'email' => 'single@example.test'],
        ];

        foreach ($orders as $orderData) {
            Order::query()->create([
                'user_id' => $orderData['user_id'],
                'order_number' => $orderData['number'],
                'customer_name' => $orderData['user_id'] ? $customer->name : 'Guest Customer',
                'customer_phone' => '555-0101',
                'customer_email' => $orderData['email'],
                'shipping_address' => 'Analytics test address',
                'status' => 'delivered',
                'payment_status' => 'paid',
                'payment_method' => 'manual',
                'subtotal' => 10,
                'discount' => 0,
                'shipping_cost' => 0,
                'tax' => 0,
                'total' => 10,
                'placed_at' => now(),
            ]);
        }

        $product = Product::query()->create([
            'name' => 'Analytics Review Product',
            'slug' => 'analytics-review-product',
            'is_active' => true,
        ]);

        ProductReview::query()->create([
            'product_id' => $product->getKey(),
            'reviewer_name' => 'Reviewer One',
            'reviewer_email' => 'reviewer-one@example.test',
            'rating' => 5,
            'status' => 'approved',
        ]);
        ProductReview::query()->create([
            'product_id' => $product->getKey(),
            'reviewer_name' => 'Reviewer Two',
            'reviewer_email' => 'reviewer-two@example.test',
            'rating' => 3,
            'status' => 'approved',
        ]);
        ProductReview::query()->create([
            'product_id' => $product->getKey(),
            'reviewer_name' => 'Reviewer Pending',
            'reviewer_email' => 'reviewer-pending@example.test',
            'rating' => 2,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('commerce-admin.analytics'))
            ->assertOk()
            ->assertSee('66.7%')
            ->assertSee('4.0')
            ->assertSee('2 نظر تأییدشده')
            ->assertSee('1 نظر در انتظار بررسی');
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

        $paidAggregateQueries = [];
        $reviewAggregateQueries = [];
        $customerAggregateQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$paidAggregateQueries, &$reviewAggregateQueries, &$customerAggregateQueries): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'paid_count') && str_contains($sql, 'paid_revenue')) {
                $paidAggregateQueries[] = $query->sql;
            }
            if (str_contains($sql, 'from "product_reviews"') && str_contains($sql, 'group by') && str_contains($sql, 'rating')) {
                $reviewAggregateQueries[] = $query->sql;
            }
            if (str_contains($sql, 'identified_customers') && str_contains($sql, 'repeat_customers')) {
                $customerAggregateQueries[] = $query->sql;
            }
        });

        $this->get(route('commerce-admin.analytics'))
            ->assertOk()
            ->assertSee('0 تومان');

        $this->assertCount(1, $paidAggregateQueries, 'Paid revenue and paid-order count should share one SQL aggregate.');
        $this->assertCount(1, $reviewAggregateQueries, 'Review counters and distribution should be computed by one grouped query.');
        $this->assertCount(1, $customerAggregateQueries, 'Returning-customer metrics should use one grouped source query.');

    }
}
