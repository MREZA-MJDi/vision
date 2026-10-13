<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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

        return view('commerce-admin::dashboard.index', compact(
            'revenue', 'orderCount', 'pendingOrders', 'productsCount', 'activeProducts',
            'lowStockCount', 'customersCount', 'series', 'recentOrders', 'recentProducts'
        ));
    }

    public function analytics(): View
    {
        $orderModel = $this->model('order', Order::class);
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $orderItemModel = $this->model('order_item', \App\Models\OrderItem::class);

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

        return view('commerce-admin::dashboard.analytics', compact(
            'revenue', 'allTimeOrders', 'paidCount', 'cancelledCount', 'series',
            'byCity', 'paymentBreakdown', 'topProducts', 'lowStock', 'activeProducts'
        ));
    }

    /**
     * Aggregate each month in a separate database query to keep this portable
     * across MySQL/MariaDB and SQLite test environments.
     */
    private function revenueSeries(string $orderModel): array
    {
        $points = [];
        $anchor = Carbon::now()->startOfMonth()->subMonths(7);

        for ($i = 0; $i < 8; $i++) {
            $start = $anchor->copy()->addMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $amount = $orderModel::query()
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->whereRaw('COALESCE(placed_at, created_at) >= ? AND COALESCE(placed_at, created_at) <= ?', [
                    $start->toDateTimeString(),
                    $end->toDateTimeString(),
                ])->sum('total');

            $points[] = [
                'label' => $start->format('M'),
                'full_label' => $start->format('F Y'),
                'value' => (float) $amount,
            ];
        }

        return $points;
    }
}
