<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
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
        $variantModel = $this->model('variant', ProductVariant::class);
        $orderItemModel = $this->model('order_item', OrderItem::class);
        $paymentModel = $this->model('payment', Payment::class);
        $reviewModel = $this->model('review', ProductReview::class);

        $orders = $orderModel::query();
        $paid = (clone $orders)->where('payment_status', 'paid')->whereNotIn('status', ['cancelled', 'returned']);
        $paidMetrics = (clone $paid)
            ->selectRaw('COUNT(*) as paid_count, COALESCE(SUM(total), 0) as paid_revenue')
            ->first();
        $revenue = (float) ($paidMetrics?->paid_revenue ?? 0);
        $paidCount = (int) ($paidMetrics?->paid_count ?? 0);
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

        // One grouped read supplies the approved distribution, weighted average and pending count.
        $reviewStats = $reviewModel::query()
            ->select('status', 'rating')
            ->selectRaw('COUNT(*) as aggregate')
            ->whereIn('status', ['approved', 'pending'])
            ->groupBy('status', 'rating')
            ->get();

        $reviewDistribution = $reviewStats
            ->where('status', 'approved')
            ->mapWithKeys(static fn ($row) => [(int) $row->rating => (int) $row->aggregate])
            ->all();
        $reviewsCount = array_sum($reviewDistribution);
        $weightedRating = 0;
        foreach ($reviewDistribution as $rating => $count) {
            $weightedRating += (int) $rating * (int) $count;
        }

        $reviewAverage = $reviewsCount > 0
            ? round($weightedRating / $reviewsCount, 2)
            : 0.0;
        $pendingReviews = (int) $reviewStats->where('status', 'pending')->sum('aggregate');

        // Count distinct registered customers and guest emails from grouped source sets.
        // This avoids running separate DISTINCT and HAVING count scans against paid orders.
        $registeredCustomerOrders = (clone $paid)
            ->whereNotNull('user_id')
            ->selectRaw('user_id as customer_identity, COUNT(*) as order_count')
            ->groupBy('user_id');
        $guestCustomerOrders = (clone $paid)
            ->whereNull('user_id')
            ->whereNotNull('customer_email')
            ->where('customer_email', '<>', '')
            ->selectRaw('LOWER(customer_email) as customer_identity, COUNT(*) as order_count')
            ->groupByRaw('LOWER(customer_email)');

        $customerMetrics = DB::query()
            ->fromSub($registeredCustomerOrders->unionAll($guestCustomerOrders), 'customer_order_counts')
            ->selectRaw(
                'COUNT(*) as identified_customers,
                COALESCE(SUM(CASE WHEN order_count > 1 THEN 1 ELSE 0 END), 0) as repeat_customers'
            )
            ->first();

        $identifiedCustomers = (int) ($customerMetrics?->identified_customers ?? 0);
        $repeatCustomers = (int) ($customerMetrics?->repeat_customers ?? 0);
        $returningRate = $identifiedCustomers > 0
            ? round(($repeatCustomers / $identifiedCustomers) * 100, 1)
            : 0;

        return view('commerce-admin::dashboard.analytics', compact(
            'revenue',
            'paidCount',
            'series',
            'byCity',
            'paymentBreakdown',
            'topProducts',
            'lowStock',
            'reviewsCount',
            'reviewAverage',
            'reviewDistribution',
            'pendingReviews',
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
