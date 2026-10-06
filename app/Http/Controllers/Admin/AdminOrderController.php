<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->withCount('items')
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $search = $request->string('q')->toString();

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where(
                                'order_number',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'customer_name',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'customer_phone',
                                'like',
                                '%' . $search . '%'
                            );
                    });
                }
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('order_type'),
                fn ($query) => $query->where(
                    'order_type',
                    $request->input('order_type')
                )
            )
            ->when(
                $request->filled('payment_status'),
                fn ($query) => $query->where(
                    'payment_status',
                    $request->input('payment_status')
                )
            )
            ->latest('placed_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,

            'statusNames' => $this->statusNames(),
            'orderTypeNames' => [
                'retail' => 'خرده',
                'wholesale' => 'عمده',
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'address',
            'items.product',
            'items.productVariant',
            'payments',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'statusNames' => $this->statusNames(),
            'paymentStatusNames' => $this->paymentStatusNames(),
        ]);
    }

    public function update(
        UpdateOrderRequest $request,
        Order $order,
        OrderService $orders
    ): RedirectResponse {
        try {
            $data = $request->validated();

            $orders->updateStatus(
                $order,
                $data['status'],
                $data['payment_status'],
                $data['customer_note'] ?? null,
                $data['tracking_code'] ?? null
            );

            return back()->with(
                'success',
                "سفارش {$order->order_number} با موفقیت به‌روزرسانی شد."
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'به‌روزرسانی سفارش انجام نشد.'
                );
        }
    }

    private function statusNames(): array
    {
        return [
            'pending' => 'در انتظار',
            'confirmed' => 'تأیید شده',
            'preparing' => 'در حال آماده‌سازی',
            'shipped' => 'ارسال شده',
            'delivered' => 'تحویل شده',
            'cancelled' => 'لغو شده',
            'returned' => 'مرجوعی',
        ];
    }

    private function paymentStatusNames(): array
    {
        return [
            'pending' => 'در انتظار',
            'paid' => 'پرداخت شده',
            'failed' => 'ناموفق',
            'refunded' => 'بازپرداخت شده',
        ];
    }
}
