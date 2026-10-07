<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\FinancialTransaction;
use App\Models\IntegrationMapping;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /*
        |--------------------------------------------------------------------------
        | Selected period
        |--------------------------------------------------------------------------
        */

        $period = max(
            7,
            min($request->integer('period', 30), 90)
        );

        $from = now()
            ->startOfDay()
            ->subDays($period - 1);

        $to = now()->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | Orders in selected period
        |--------------------------------------------------------------------------
        */

        $baseOrders = Order::query()
            ->whereBetween('placed_at', [$from, $to]);


        /*
        |--------------------------------------------------------------------------
        | Accounting summary
        |
        | The dashboard reads recognized revenue and settled cash/bank
        | from the double-entry ledger. Legacy manual expenses remain
        | visible until the manual-expense posting path is migrated.
        |--------------------------------------------------------------------------
        */

        $ledgerTotals = JournalLine::query()
            ->whereHas('entry', function ($query) use ($from, $to): void {
                $query
                    ->whereDate('entry_date', '>=', $from->toDateString())
                    ->whereDate('entry_date', '<=', $to->toDateString());
            })
            ->whereHas('account', fn ($query) => $query->whereIn(
                'code',
                ['sales', 'sales_returns', 'cash', 'bank']
            ))
            ->selectRaw('ledger_account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->groupBy('ledger_account_id')
            ->get();

        $ledgerAccounts = LedgerAccount::query()
            ->whereIn('id', $ledgerTotals->pluck('ledger_account_id'))
            ->pluck('code', 'id');

        $ledgerByCode = $ledgerTotals->keyBy(
            fn ($row) => $ledgerAccounts->get($row->ledger_account_id)
        );

        $revenue = (float) ($ledgerByCode->get('sales')?->credit_total ?? 0);
        $salesReturns = (float) ($ledgerByCode->get('sales_returns')?->debit_total ?? 0);
        $revenue -= $salesReturns;

        $settledFundsIn = (float) $ledgerByCode
            ->filter(fn ($row, $code) => in_array($code, ['cash', 'bank'], true))
            ->sum('debit_total');

        $settledFundsOut = (float) $ledgerByCode
            ->filter(fn ($row, $code) => in_array($code, ['cash', 'bank'], true))
            ->sum('credit_total');

        $settledFunds = $settledFundsIn - $settledFundsOut;

        $expenses = (float) FinancialTransaction::query()
            ->where('type', 'expense')
            // Refunds are already reflected as credits to cash/bank in the ledger.
            ->where(fn ($query) => $query
                ->whereNull('category')
                ->orWhere('category', '!=', 'refund'))
            ->whereDate('transaction_date', '>=', $from->toDateString())
            ->whereDate('transaction_date', '<=', $to->toDateString())
            ->sum('amount');

        $netCash = $settledFunds - $expenses;


        /*
        |--------------------------------------------------------------------------
        | Daily sales chart
        |--------------------------------------------------------------------------
        */

        $orderRows = (clone $baseOrders)
            ->selectRaw(
                'DATE(placed_at) as day, COUNT(*) as orders'
            )
            ->groupByRaw('DATE(placed_at)')
            ->get()
            ->keyBy('day');

        $ledgerIncomeRows = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->whereDate('journal_entries.entry_date', '>=', $from->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $to->toDateString())
            ->whereIn('ledger_accounts.code', ['sales', 'sales_returns'])
            ->selectRaw("DATE(journal_entries.entry_date) as day,
                SUM(CASE
                    WHEN ledger_accounts.code = 'sales' THEN journal_lines.credit - journal_lines.debit
                    WHEN ledger_accounts.code = 'sales_returns' THEN journal_lines.credit - journal_lines.debit
                    ELSE 0
                END) as income")
            ->groupByRaw('DATE(journal_entries.entry_date)')
            ->get()
            ->keyBy('day');

        $daily = collect(range(0, $period - 1))
            ->map(function (int $index) use ($from, $orderRows, $ledgerIncomeRows): array {
                $day = $from->copy()->addDays($index);

                $orders = $orderRows->get(
                    $day->toDateString()
                );
                $ledgerIncome = $ledgerIncomeRows->get($day->toDateString());

                return [
                    'label' => $day->format('m/d'),
                    'date' => $day->toDateString(),

                    'income' => (float) ($ledgerIncome->income ?? 0),

                    'orders' => (int) ($orders->orders ?? 0),
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        $lowStockVariants = ProductVariant::query()
            ->with([
                'product:id,name',
            ])
            ->where('is_active', true)
            ->whereColumn(
                'stock',
                '<=',
                'low_stock_threshold'
            )
            ->orderBy('stock')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $lowStock = ProductVariant::query()
            ->where('is_active', true)
            ->whereColumn(
                'stock',
                '<=',
                'low_stock_threshold'
            )
            ->count();

        $inventoryValue = (float) ProductVariant::query()
            ->where('is_active', true)
            ->selectRaw(
                'COALESCE(
                    SUM(
                        stock * COALESCE(sale_price, price)
                    ),
                    0
                ) as total'
            )
            ->value('total');


        /*
        |--------------------------------------------------------------------------
        | Dashboard calculations
        |--------------------------------------------------------------------------
        */

        $dailyMaxIncome = (float) $daily->max(
            fn (array $row) => abs((float) $row['income'])
        );

        $paidOrdersCount = (clone $baseOrders)
            ->where('payment_status', 'paid')
            ->count();

        $ordersCount = (clone $baseOrders)
            ->count();

        $orderBreakdown = (clone $baseOrders)
            ->selectRaw(
                'status, COUNT(*) as total'
            )
            ->groupBy('status')
            ->pluck('total', 'status');


        /*
        |--------------------------------------------------------------------------
        | Current processing orders
        |
        | IMPORTANT:
        | This is intentionally NOT limited to the selected period.
        | "Current processing" means orders whose current status is
        | pending / confirmed / preparing, regardless of when they
        | were originally placed.
        |--------------------------------------------------------------------------
        */

        $pendingOrders = Order::query()
            ->activeProcessing()
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        $customers = User::query()
            ->customers()
            ->count();

        $unreadContactMessages = ContactMessage::query()
            ->unread()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Catalog control metrics
        |--------------------------------------------------------------------------
        */

        $catalogProducts = Product::query()->count();
        $catalogBrands = Brand::query()->count();
        $catalogCategories = Category::query()->count();
        $catalogVariants = ProductVariant::query()->where('is_active', true)->count();
        $productsMissingSeo = Product::query()
            ->active()
            ->where(fn ($query) => $query
                ->whereNull('meta_title')->orWhereRaw("TRIM(COALESCE(meta_title, '')) = ''")
                ->orWhereNull('meta_description')->orWhereRaw("TRIM(COALESCE(meta_description, '')) = ''")
            )
            ->count();

        $productsMissingImage = Product::query()
            ->active()
            ->whereDoesntHave('galleryMedia')
            ->count();

        $categoriesMissingSeo = Category::query()
            ->active()
            ->where(fn ($query) => $query
                ->whereNull('meta_title')->orWhereRaw("TRIM(COALESCE(meta_title, '')) = ''")
                ->orWhereNull('meta_description')->orWhereRaw("TRIM(COALESCE(meta_description, '')) = ''")
            )
            ->count();

        $brandsMissingSeo = Brand::query()
            ->active()
            ->where(fn ($query) => $query
                ->whereNull('meta_title')->orWhereRaw("TRIM(COALESCE(meta_title, '')) = ''")
                ->orWhereNull('meta_description')->orWhereRaw("TRIM(COALESCE(meta_description, '')) = ''")
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Management queues
        |--------------------------------------------------------------------------
        */

        $nilaMappings = IntegrationMapping::query()
            ->where('integration', 'nila');

        $nilaProductMappings = (clone $nilaMappings)
            ->where('entity_type', Product::class)
            ->count();

        $nilaVariantMappings = (clone $nilaMappings)
            ->where('entity_type', ProductVariant::class)
            ->count();

        $nilaLastMappedAt = (clone $nilaMappings)
            ->latest('updated_at')
            ->value('updated_at');


        /*
        |--------------------------------------------------------------------------
        | Recent orders
        |
        | This is intentionally global/latest, not period-limited.
        |--------------------------------------------------------------------------
        */

        $recentOrders = Order::query()
            ->with('user')
            ->latest('placed_at')
            ->latest('id')
            ->limit(7)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Recent products
        |--------------------------------------------------------------------------
        |
        | Keep catalog visibility separate from sales analytics.
        | Newly created products should be visible even before they sell.
        |--------------------------------------------------------------------------
        */

        $recentProducts = Product::query()
            ->with([
                'category:id,name',
                'primaryGalleryMedia',
                'primaryActiveVariant',
            ])
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Top products in the selected period
        |
        | Exclude cancelled/returned orders and unpaid orders so the
        | ranking reflects paid product quantity for this report range.
        |--------------------------------------------------------------------------
        */
        $topProductSalesFilter = function ($query) use ($from, $to) {
            $query->whereHas('order', function ($orderQuery) use ($from, $to) {
                $orderQuery
                    ->whereBetween('placed_at', [$from, $to])
                    ->whereNotIn(
                        'status',
                        Order::CANCEL_LIKE_STATUSES
                    )
                    ->where('payment_status', 'paid');
            });
        };

        $topProducts = Product::query()
            ->with([
                'category:id,name',
            ])
            ->whereHas('orderItems', $topProductSalesFilter)
            ->withSum(
                [
                    'orderItems as sales_quantity' => $topProductSalesFilter,
                ],
                'quantity'
            )
            ->orderByDesc('sales_quantity')
            ->orderBy('id')
            ->limit(6)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Status labels
        |--------------------------------------------------------------------------
        */

        $statusNames = [
            'pending' => 'در انتظار',
            'confirmed' => 'تأیید شده',
            'preparing' => 'در حال آماده‌سازی',
            'shipped' => 'ارسال شده',
            'delivered' => 'تحویل شده',
            'cancelled' => 'لغو شده',
            'returned' => 'مرجوعی',
        ];


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'admin.dashboard.index',
            [
                'period' => $period,

                'revenue' => $revenue,

                'expenses' => $expenses,

                'netCash' => $netCash,

                'settledFunds' => $settledFunds,

                'salesReturns' => $salesReturns,

                'paidOrdersCount' => $paidOrdersCount,

                'ordersCount' => $ordersCount,

                'pendingOrders' => $pendingOrders,

                'customers' => $customers,

                'unreadContactMessages' => $unreadContactMessages,

                'catalogProducts' => $catalogProducts,
                'catalogBrands' => $catalogBrands,
                'catalogCategories' => $catalogCategories,
                'catalogVariants' => $catalogVariants,
                'productsMissingSeo' => $productsMissingSeo,
                'productsMissingImage' => $productsMissingImage,
                'categoriesMissingSeo' => $categoriesMissingSeo,
                'brandsMissingSeo' => $brandsMissingSeo,


                'nilaProductMappings' => $nilaProductMappings,
                'nilaVariantMappings' => $nilaVariantMappings,
                'nilaLastMappedAt' => $nilaLastMappedAt,

                'lowStock' => $lowStock,

                'inventoryValue' => $inventoryValue,

                'daily' => $daily,

                'maxIncome' => max(
                    1,
                    $dailyMaxIncome
                ),

                'orderBreakdown' => $orderBreakdown,

                'statusNames' => $statusNames,

                'lowStockVariants' => $lowStockVariants,

                'recentOrders' => $recentOrders,

                'topProducts' => $topProducts,

                'recentProducts' => $recentProducts,
            ]
        );
    }
}
