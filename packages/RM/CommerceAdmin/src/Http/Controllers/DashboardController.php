<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductReview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController
{
    private function model(string $key, string $fallback): string
    {
        return (string) config('commerce-admin.models.' . $key, $fallback);
    }

    public function index(): View
    {
        $orderModel = $this->model('order', Order::class);
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $userModel = $this->model('user', User::class);

        $orders = $orderModel::query();
        $paidOrders = (clone $orders)->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'returned']);

        $revenue = (clone $paidOrders)->sum('total');
        $orderCount = (clone $orders)->count();
        $pendingOrders = (clone $orders)->whereIn('status', ['pending', 'confirmed', 'preparing'])->count();
        $productsCount = $productModel::query()->count();
        $activeProducts = $productModel::query()->where('is_active', true)->count();
        $lowStockCount = $variantModel::query()->where('is_active', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')->count();

        $adminFlag = config('commerce-admin.admin_flag_column', 'is_admin');
        $customersCount = $userModel::query()->where($adminFlag, false)->count();

        $series = $this->revenueSeries($orderModel);
        $recentOrders = $orderModel::query()->with(['items', 'user'])
            ->orderByDesc('created_at')->limit(7)->get();
        $recentProducts = $productModel::query()->with(['category', 'brand', 'variants', 'galleryMedia'])
            ->orderByDesc('created_at')->limit(5)->get();
        $recentCustomers = $userModel::query()->where($adminFlag, false)
            ->orderByDesc('created_at')->limit(4)->get();

        return view('commerce-admin::dashboard.index', compact(
            'revenue', 'orderCount', 'pendingOrders', 'productsCount', 'activeProducts',
            'lowStockCount', 'customersCount', 'series', 'recentOrders', 'recentProducts',
            'recentCustomers'
        ));
    }

    public function analytics(): View
    {
        $orderModel = $this->model('order', Order::class);
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $orderItemModel = $this->model('order_item', \App\Models\OrderItem::class);
        $reviewModel = $this->model('review', ProductReview::class);

        $orders = $orderModel::query();
        $paid = (clone $orders)->where('payment_status', 'paid')->whereNotIn('status', ['cancelled', 'returned']);
        $revenue = (clone $paid)->sum('total');
        $allTimeOrders = (clone $orders)->count();
        $paidCount = (clone $paid)->count();
        $cancelledCount = (clone $orders)->whereIn('status', ['cancelled', 'returned'])->count();
        $series = $this->revenueSeries($orderModel);

        $byCity = $orderModel::query()
            ->select('shipping_city', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as revenue_total'))
            ->whereNotNull('shipping_city')
            ->where('shipping_city', '<>', '')
            ->groupBy('shipping_city')
            ->orderByDesc('revenue_total')->limit(5)->get();

        $paymentBreakdown = $orderModel::query()
            ->select('payment_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('payment_status')->get();

        $topProducts = $orderItemModel::query()
            ->select('product_name', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(line_total) as gross_sales'))
            ->groupBy('product_name')->orderByDesc('units_sold')->limit(5)->get();

        $lowStock = $variantModel::query()->with('product')
            ->where('is_active', true)->whereColumn('stock', '<=', 'low_stock_threshold')
            ->orderBy('stock')->limit(8)->get();

        $activeProducts = $productModel::query()->where('is_active', true)->count();

        $approvedReviews = $reviewModel::query()->where('status', 'approved');
        $reviewsCount = (clone $approvedReviews)->count();
        $reviewAverage = round((float) (clone $approvedReviews)->avg('rating'), 2);
        $reviewDistribution = (clone $approvedReviews)
            ->select('rating', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('rating')
            ->pluck('aggregate', 'rating')
            ->all();
        $pendingReviews = $reviewModel::query()->where('status', 'pending')->count();
        $recentReviews = $reviewModel::query()->with(['product', 'user'])
            ->where('status', 'approved')->orderByDesc('created_at')->limit(5)->get();

        $identifiedCustomers = (clone $orders)->whereNotNull('user_id')->distinct()->count('user_id')
            + (clone $orders)->whereNull('user_id')->whereNotNull('customer_email')
                ->where('customer_email', '<>', '')->distinct()->count('customer_email');

        $repeatUserCustomers = $orderModel::query()->whereNotNull('user_id')
            ->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->get()->count();
        $repeatEmailCustomers = $orderModel::query()->whereNull('user_id')
            ->whereNotNull('customer_email')->where('customer_email', '<>', '')
            ->selectRaw('LOWER(customer_email) as normalized_email')
            ->groupByRaw('LOWER(customer_email)')->havingRaw('COUNT(*) > 1')->get()->count();
        $returningRate = $identifiedCustomers > 0
            ? round((($repeatUserCustomers + $repeatEmailCustomers) / $identifiedCustomers) * 100, 1)
            : 0;

        return view('commerce-admin::dashboard.analytics', compact(
            'revenue', 'allTimeOrders', 'paidCount', 'cancelledCount', 'series',
            'byCity', 'paymentBreakdown', 'topProducts', 'lowStock', 'activeProducts',
            'reviewsCount', 'reviewAverage', 'reviewDistribution', 'pendingReviews', 'recentReviews',
            'identifiedCustomers', 'returningRate'
        ));
    }

    /**
     * Aggregate monthly values in separate queries so this remains portable
     * across MySQL/MariaDB and SQLite test environments.
     */
    private function revenueSeries(string $orderModel): array
    {
        $points = [];
        $anchor = Carbon::now()->startOfMonth()->subMonths(11);

        for ($i = 0; $i < 12; $i++) {
            $start = $anchor->copy()->addMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $range = [
                $start->toDateTimeString(),
                $end->toDateTimeString(),
            ];
            $amount = $orderModel::query()
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->whereRaw('COALESCE(placed_at, created_at) >= ? AND COALESCE(placed_at, created_at) <= ?', $range)
                ->sum('total');
            $ordersCount = $orderModel::query()
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->whereRaw('COALESCE(placed_at, created_at) >= ? AND COALESCE(placed_at, created_at) <= ?', $range)
                ->count();

            $points[] = [
                'label' => $start->format('M'),
                'full_label' => $start->format('F Y'),
                'value' => (float) $amount,
                'orders_count' => (int) $ordersCount,
            ];
        }

        return $points;
    }
}
