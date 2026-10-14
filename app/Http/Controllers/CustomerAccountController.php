<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Auth\AuthenticatedUserRedirector;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerAccountController extends Controller
{
    public function show(Request $request, AuthenticatedUserRedirector $redirector): View|RedirectResponse
    {
        $customer = $request->user();

        if ($redirector->hasAdminAccess($customer)) {
            return redirect()->route('commerce-admin.dashboard');
        }

        $orders = Order::query()
            ->where('user_id', $customer->getKey())
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('store.account', compact('customer', 'orders'));
    }

    public function order(Request $request, int $order, AuthenticatedUserRedirector $redirector): View|RedirectResponse
    {
        $customer = $request->user();

        if ($redirector->hasAdminAccess($customer)) {
            return redirect()->route('commerce-admin.dashboard');
        }

        $model = Order::query()
            ->with('items')
            ->where('user_id', $customer->getKey())
            ->whereKey($order)
            ->firstOrFail();

        return view('store.account-order', compact('customer', 'model'));
    }
}
