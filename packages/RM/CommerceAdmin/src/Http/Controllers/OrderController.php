<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController
{
    private function model(string $key, string $fallback): string
    {
        return (string) config('commerce-admin.models.' . $key, $fallback);
    }

    public function index(Request $request): View
    {
        $orderModel = $this->model('order', Order::class);
        $orderItemModel = $this->model('order_item', OrderItem::class);

        $query = $orderModel::query()->with(['user', 'items']);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('order_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'pending') {
            $query->whereIn('status', ['pending', 'confirmed', 'preparing']);
        } elseif ($status === 'shipped') {
            $query->whereIn('status', ['shipped', 'out_for_delivery']);
        } elseif ($status === 'cancelled') {
            $query->whereIn('status', ['cancelled', 'returned']);
        } elseif ($status !== 'all' && in_array($status, Order::STATUSES, true)) {
            $query->where('status', $status);
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'all' => $orderModel::query()->count(),
            'pending' => $orderModel::query()->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'shipped' => $orderModel::query()->whereIn('status', ['shipped', 'out_for_delivery'])->count(),
            'delivered' => $orderModel::query()->where('status', 'delivered')->count(),
            'cancelled' => $orderModel::query()->whereIn('status', ['cancelled', 'returned'])->count(),
            'gross' => $orderItemModel::query()->sum('line_total'),
        ];

        // The paginator already eager-loads order items, so the table stays query-bounded.


        return view('commerce-admin::orders.index', compact('orders', 'stats', 'search', 'status'));
    }

    public function create(): View
    {
        $variantModel = $this->model('variant', ProductVariant::class);
        $variants = $variantModel::query()->with(['product', 'primaryGalleryMedia', 'product.primaryGalleryMedia'])
            ->where('is_active', true)->orderBy('product_id')->orderBy('sort_order')->get()
            ->filter(fn ($variant) => $variant->product && $variant->product->is_active);

        return view('commerce-admin::orders.create', compact('variants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orderModel = $this->model('order', Order::class);
        $itemModel = $this->model('order_item', OrderItem::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $inventoryModel = $this->model('inventory_movement', InventoryMovement::class);
        $userModel = $this->model('user', User::class);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'shipping_province' => ['nullable', 'string', 'max:100'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer', 'manual'])],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($validated, $orderModel, $itemModel, $variantModel, $inventoryModel, $userModel) {
            $taxRate = max(0, (float) config('commerce-admin.tax_rate', 0));
            $email = $validated['customer_email'] ?? null;
            $customer = $email ? $userModel::query()->where('email', $email)->first() : null;
            $number = 'VS-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(4));
            $subtotal = 0.0;
            $taxAmount = 0.0;
            $preparedItems = [];

            foreach ($validated['items'] as $index => $input) {
                $variant = $variantModel::query()
                    ->with(['product', 'primaryGalleryMedia', 'product.primaryGalleryMedia'])
                    ->whereKey($input['variant_id'])->lockForUpdate()->firstOrFail();

                if (! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
                    throw ValidationException::withMessages([
                        "items.$index.variant_id" => 'This product option is not available for sale.',
                    ]);
                }

                $quantity = (int) $input['quantity'];
                if ((int) $variant->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => 'Requested quantity exceeds the current stock for ' . $variant->product->name . '.',
                    ]);
                }

                $unitPrice = (float) ($variant->sale_price ?? $variant->price);
                $lineTotal = round($unitPrice * $quantity, 2);
                $lineTax = (bool) $variant->product->getAttribute('is_taxable')
                    ? round($lineTotal * $taxRate, 2)
                    : 0.0;
                $subtotal += $lineTotal;
                $taxAmount += $lineTax;
                $preparedItems[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'tax_amount' => $lineTax,
                    'image' => $variant->primaryGalleryMedia?->url
                        ?? $variant->product->primaryGalleryMedia?->url,
                ];
            }

            $subtotal = round($subtotal, 2);
            $taxAmount = round($taxAmount, 2);
            $shipping = round((float) $validated['shipping_cost'], 2);
            $order = $orderModel::query()->create([
                'user_id' => $customer?->getKey(),
                'address_id' => null,
                'order_number' => $number,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_email' => $email,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'shipping_province' => $validated['shipping_province'] ?? null,
                'shipping_city' => $validated['shipping_city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping_cost' => $shipping,
                'tax' => $taxAmount,
                'total' => round($subtotal + $shipping + $taxAmount, 2),
                'placed_at' => now(),
            ]);

            $actorId = Auth::guard(config('commerce-admin.guard', 'web'))->id();
            foreach ($preparedItems as $line) {
                $variant = $line['variant'];
                $product = $variant->product;
                $before = (int) $variant->stock;
                $after = $before - $line['quantity'];

                $itemModel::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => $product->getKey(),
                    'product_variant_id' => $variant->getKey(),
                    'product_name' => $product->name,
                    'variant_name' => $variant->display_name,
                    'sku' => $variant->sku,
                    'product_image' => $line['image'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                    'tax_amount' => $line['tax_amount'],
                ]);

                $variant->stock = $after;
                $variant->save();

                $inventoryModel::query()->create([
                    'product_variant_id' => $variant->getKey(),
                    'type' => 'sale',
                    'quantity' => -$line['quantity'],
                    'stock_after' => $after,
                    'reference_type' => $order->getMorphClass(),
                    'reference_id' => $order->getKey(),
                    'note' => 'Stock reserved for order ' . $order->order_number,
                    'created_by' => $actorId,
                ]);
            }

            return $order;
        });

        return redirect()->route('commerce-admin.orders.show', $order->getKey())
            ->with('status', 'Order created and stock updated.');
    }

    public function show(int $order): View
    {
        $orderModel = $this->model('order', Order::class);
        $itemModel = $this->model('order_item', OrderItem::class);
        $model = $orderModel::query()->with(['user', 'payments'])->findOrFail($order);
        $items = $itemModel::query()->where('order_id', $model->getKey())->get();

        return view('commerce-admin::orders.show', compact('model', 'items'));
    }

    public function updateStatus(Request $request, int $order): RedirectResponse
    {
        $orderModel = $this->model('order', Order::class);
        $itemModel = $this->model('order_item', OrderItem::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $inventoryModel = $this->model('inventory_movement', InventoryMovement::class);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        DB::transaction(function () use ($order, $validated, $orderModel, $itemModel, $variantModel, $inventoryModel): void {
            $model = $orderModel::query()->whereKey($order)->lockForUpdate()->firstOrFail();
            $next = $validated['status'];
            $terminal = ['cancelled', 'returned'];
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['preparing', 'cancelled'],
                'preparing' => ['shipped', 'cancelled'],
                'shipped' => ['out_for_delivery', 'returned'],
                'out_for_delivery' => ['delivered', 'returned'],
                'delivered' => ['returned'],
                'cancelled' => [],
                'returned' => [],
            ];

            if ($next !== $model->status && ! in_array($next, $allowedTransitions[$model->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'That status change is not allowed from the current order state.',
                ]);
            }

            $wasTerminal = in_array($model->status, $terminal, true);
            $willBeTerminal = in_array($next, $terminal, true);

            if (! $wasTerminal && $willBeTerminal) {
                $actorId = Auth::guard(config('commerce-admin.guard', 'web'))->id();
                $items = $itemModel::query()->where('order_id', $model->getKey())->get();

                foreach ($items as $item) {
                    if (! $item->product_variant_id) {
                        continue;
                    }

                    $variant = $variantModel::query()->whereKey($item->product_variant_id)->lockForUpdate()->first();
                    if (! $variant) {
                        continue;
                    }

                    $variant->stock = (int) $variant->stock + (int) $item->quantity;
                    $variant->save();

                    $inventoryModel::query()->create([
                        'product_variant_id' => $variant->getKey(),
                        'type' => $next === 'returned' ? 'return' : 'adjustment',
                        'quantity' => (int) $item->quantity,
                        'stock_after' => (int) $variant->stock,
                        'reference_type' => $model->getMorphClass(),
                        'reference_id' => $model->getKey(),
                        'note' => 'Stock restored after order ' . $next . ' (' . $model->order_number . ')',
                        'created_by' => $actorId,
                    ]);
                }
            }

            $model->status = $next;
            if (in_array($next, ['shipped', 'out_for_delivery', 'delivered'], true) && ! $model->shipped_at) {
                $model->shipped_at = now();
            }
            if ($next === 'out_for_delivery' && ! $model->out_for_delivery_at) {
                $model->out_for_delivery_at = now();
            }
            if ($next === 'delivered' && ! $model->delivered_at) {
                $model->delivered_at = now();
            }
            if ($next === 'cancelled' && ! $model->cancelled_at) {
                $model->cancelled_at = now();
            }
            $model->save();
        });

        return redirect()->route('commerce-admin.orders.show', $order)
            ->with('status', 'Order status updated.');
    }

    public function updatePayment(Request $request, int $order): RedirectResponse
    {
        $orderModel = $this->model('order', Order::class);
        $model = $orderModel::query()->findOrFail($order);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(Payment::STATUSES)],
        ]);

        if ($validated['payment_status'] === 'refunded' && $model->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'payment_status' => 'Only a paid order can be marked as refunded.',
            ]);
        }

        $model->payment_status = $validated['payment_status'];
        if ($model->payment_status === 'paid' && ! $model->paid_at) {
            $model->paid_at = now();
        }
        $model->save();

        return redirect()->route('commerce-admin.orders.show', $order)
            ->with('status', 'Payment status updated manually. Gateway reconciliation is separate.');
    }
}
