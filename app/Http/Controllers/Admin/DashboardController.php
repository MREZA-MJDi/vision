<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
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
        $variantModel = $this->model('variant', ProductVariant::class);
        $userModel = $this->model('user', User::class);
        $adminFlag = (string) config('commerce-admin.admin_flag_column', 'is_admin');

        // Fetch revenue and order KPIs in one scan instead of three independent aggregates.
        // The conditional sums are portable across the supported SQL database drivers.
        $orderMetrics = $orderModel::query()
            ->selectRaw("
                COUNT(*) as order_count,
                COALESCE(SUM(CASE
                    WHEN payment_status = 'paid' AND status NOT IN ('cancelled', 'returned')
                    THEN total ELSE 0
                END), 0) as paid_revenue,
                COALESCE(SUM(CASE
                    WHEN status IN ('pending', 'confirmed', 'preparing')
                    THEN 1 ELSE 0
                END), 0) as pending_orders
            ")
            ->first();

        $revenue = (float) ($orderMetrics?->paid_revenue ?? 0);
        $orderCount = (int) ($orderMetrics?->order_count ?? 0);
        $pendingOrders = (int) ($orderMetrics?->pending_orders ?? 0);
        $lowStockCount = $variantModel::query()->where('is_active', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')->count();
        $customersCount = $userModel::query()->where($adminFlag, false)->count();

        $series = $this->revenueSeries($orderModel);
        $recentOrders = $orderModel::query()->with('user')
            ->orderByDesc('created_at')->limit(7)->get();
        $recentCustomers = $userModel::query()->where($adminFlag, false)
            ->orderByDesc('created_at')->limit(4)->get();

        return view('commerce-admin::dashboard.index', compact(
            'revenue',
            'orderCount',
            'pendingOrders',
            'lowStockCount',
            'customersCount',
            'series',
            'recentOrders',
            'recentCustomers'
        ));
    }

    public function export()
    {
        $orderModel = $this->model('order', Order::class);
        $series = $this->revenueSeries($orderModel);

        return response()->streamDownload(function () use ($series): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Month', 'Paid revenue', 'Orders']);

            foreach ($series as $point) {
                fputcsv($stream, [
                    $point['full_label'],
                    number_format((float) $point['value'], 2, '.', ''),
                    (int) $point['orders_count'],
                ]);
            }

            fclose($stream);
        }, 'commerce-analytics.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function analytics(): View
    {
        $orderModel = $this->model('order', Order::class);
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $orderItemModel = $this->model('order_item', OrderItem::class);
        $paymentModel = $this->model('payment', Payment::class);
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
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->whereNotNull('shipping_city')
            ->where('shipping_city', '<>', '')
            ->groupBy('shipping_city')
            ->orderByDesc('revenue_total')->limit(5)->get();

        $paymentBreakdown = $paymentModel::query()
            ->select('status as payment_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')->get();

        $topProducts = $orderItemModel::query()
            ->whereHas('order', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned']))
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(line_total) as gross_sales'))
            ->groupBy('product_id', 'product_name')->orderByDesc('units_sold')->limit(5)->get();

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

        $identifiedCustomers = (clone $paid)->whereNotNull('user_id')->distinct()->count('user_id')
            + (clone $paid)->whereNull('user_id')->whereNotNull('customer_email')
                ->where('customer_email', '<>', '')->distinct()->count('customer_email');

        $repeatUserCustomers = (clone $paid)->whereNotNull('user_id')
            ->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')
            ->getQuery()->getCountForPagination();
        $repeatEmailCustomers = (clone $paid)->whereNull('user_id')
            ->whereNotNull('customer_email')->where('customer_email', '<>', '')
            ->selectRaw('LOWER(customer_email) as normalized_email')
            ->groupByRaw('LOWER(customer_email)')->havingRaw('COUNT(*) > 1')
            ->getQuery()->getCountForPagination();
        $returningRate = $identifiedCustomers > 0
            ? round((($repeatUserCustomers + $repeatEmailCustomers) / $identifiedCustomers) * 100, 1)
            : 0;

        return view('commerce-admin::dashboard.analytics', compact(
            'revenue',
            'allTimeOrders',
            'paidCount',
            'cancelledCount',
            'series',
            'byCity',
            'paymentBreakdown',
            'topProducts',
            'lowStock',
            'activeProducts',
            'reviewsCount',
            'reviewAverage',
            'reviewDistribution',
            'pendingReviews',
            'recentReviews',
            'identifiedCustomers',
            'returningRate'
        ));
    }

    /**
     * Aggregate monthly revenue and order counts in one grouped query.
     * Date filtering still uses the timestamp columns so range indexes remain useful.
     *
     * @return array<int, array{label:string,full_label:string,value:float,orders_count:int}>
     */
    private function revenueSeries(string $orderModel): array
    {
        $anchor = Carbon::now()->startOfMonth()->subMonths(11);
        $end = Carbon::now()->endOfMonth();
        $model = new $orderModel();
        $driver = DB::connection($model->getConnectionName())->getDriverName();

        $monthExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m', COALESCE(placed_at, created_at))",
            'mysql', 'mariadb' => "DATE_FORMAT(COALESCE(placed_at, created_at), '%Y-%m')",
            'pgsql' => "TO_CHAR(COALESCE(placed_at, created_at), 'YYYY-MM')",
            'sqlsrv' => "FORMAT(COALESCE(placed_at, created_at), 'yyyy-MM')",
            default => null,
        };

        $pointsByMonth = collect();

        if ($monthExpression !== null) {
            $pointsByMonth = $orderModel::query()
                ->selectRaw(
                    "{$monthExpression} as month_bucket,
                    SUM(CASE WHEN payment_status = 'paid' AND status NOT IN ('cancelled', 'returned') THEN total ELSE 0 END) as revenue_total,
                    SUM(CASE WHEN status NOT IN ('cancelled', 'returned') THEN 1 ELSE 0 END) as orders_count"
                )
                ->where(function ($query) use ($anchor, $end): void {
                    $query->whereBetween('placed_at', [$anchor, $end])
                        ->orWhere(function ($query) use ($anchor, $end): void {
                            $query->whereNull('placed_at')
                                ->whereBetween('created_at', [$anchor, $end]);
                        });
                })
                ->groupByRaw($monthExpression)
                ->get()
                ->keyBy('month_bucket');
        }

        $points = [];
        for ($i = 0; $i < 12; $i++) {
            $start = $anchor->copy()->addMonths($i)->startOfMonth();
            $aggregate = $pointsByMonth->get($start->format('Y-m'));

            if ($monthExpression === null) {
                $rangeStart = $start->copy();
                $rangeEnd = $start->copy()->endOfMonth();
                $range = fn ($query) => $query->where(function ($dateQuery) use ($rangeStart, $rangeEnd): void {
                    $dateQuery->whereBetween('placed_at', [$rangeStart, $rangeEnd])
                        ->orWhere(function ($fallbackQuery) use ($rangeStart, $rangeEnd): void {
                            $fallbackQuery->whereNull('placed_at')->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                        });
                });

                $aggregate = (object) [
                    'revenue_total' => $orderModel::query()->where($range)
                        ->where('payment_status', 'paid')->whereNotIn('status', ['cancelled', 'returned'])->sum('total'),
                    'orders_count' => $orderModel::query()->where($range)
                        ->whereNotIn('status', ['cancelled', 'returned'])->count(),
                ];
            }

            $points[] = [
                'label' => $start->format('M'),
                'full_label' => $start->format('F Y'),
                'value' => (float) ($aggregate->revenue_total ?? 0),
                'orders_count' => (int) ($aggregate->orders_count ?? 0),
            ];
        }

        return $points;
    }
}
