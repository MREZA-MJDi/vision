<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController
{
    private function model(): string
    {
        return (string) config('commerce-admin.models.user', User::class);
    }

    public function index(Request $request): View
    {
        $model = $this->model();
        $adminFlag = (string) config('commerce-admin.admin_flag_column', 'is_admin');
        $search = trim((string) $request->query('q', ''));
        $query = $model::query()
            ->where($adminFlag, false)
            ->withCount('orders')
            ->withSum([
                'orders as paid_revenue' => fn ($orders) => $orders
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', ['cancelled', 'returned']),
            ], 'total');

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%'));
        }

        $customers = $query->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'all' => $model::query()->where($adminFlag, false)->count(),
            'with_orders' => $model::query()->where($adminFlag, false)->whereHas('orders')->count(),
            'new_month' => $model::query()->where($adminFlag, false)
                ->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('commerce-admin::customers.index', compact('customers', 'search', 'stats'));
    }

    public function show(int $customer): View
    {
        $model = $this->model();
        $adminFlag = (string) config('commerce-admin.admin_flag_column', 'is_admin');
        $record = $model::query()->whereKey($customer)->where($adminFlag, false)
            ->withCount('orders')
            ->withSum([
                'orders as paid_revenue' => fn ($orders) => $orders
                    ->where('payment_status', 'paid')
                    ->whereNotIn('status', ['cancelled', 'returned']),
            ], 'total')
            ->firstOrFail();

        $orders = $record->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10), ['*'], 'orders_page')
            ->withQueryString();

        return view('commerce-admin::customers.show', compact('record', 'orders'));
    }
}
