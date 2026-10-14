<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController
{
    private function model(): string
    {
        return (string) config('commerce-admin.models.payment', Payment::class);
    }

    public function index(Request $request): View
    {
        $model = $this->model();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $query = $model::query()->with('order');

        if ($search !== '') {
            $query->where(function ($paymentQuery) use ($search): void {
                $paymentQuery->where('transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('reference_number', 'like', '%' . $search . '%')
                    ->orWhere('gateway', 'like', '%' . $search . '%')
                    ->orWhereHas('order', fn ($orderQuery) => $orderQuery
                        ->where('order_number', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%'));
            });
        }

        if (in_array($status, Payment::STATUSES, true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        $payments = $query->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'all' => $model::query()->count(),
            'paid' => $model::query()->where('status', 'paid')->count(),
            'pending' => $model::query()->where('status', 'pending')->count(),
            'refunded' => $model::query()->where('status', 'refunded')->count(),
            'paid_total' => $model::query()->where('status', 'paid')->sum('amount'),
        ];

        return view('commerce-admin::payments.index', compact('payments', 'search', 'status', 'stats'));
    }
}
