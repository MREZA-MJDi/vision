<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->customers()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $search = $request->string('q')->toString();

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
                }
            )
            ->latest('created_at')
            ->paginate(18)
            ->withQueryString();

        return view(
            'admin.customers.index',
            compact('customers')
        );
    }
}
