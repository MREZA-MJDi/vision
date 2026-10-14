<?php

namespace App\Http\Controllers\Admin;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\SearchOrderVariantsRequest;
use App\Http\Requests\Admin\StoreAdminOrderRequest;
use App\Http\Requests\Admin\UpdateOrderPaymentRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        $query = $orderModel::query()
            ->with(['user', 'primaryItem'])
            ->withSum('items as admin_total_quantity', 'quantity');
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $validStatusFilters = array_merge(['all', 'pending', 'shipped', 'cancelled'], Order::STATUSES);
        if (! in_array($status, $validStatusFilters, true)) {
            $status = 'all';
        }

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
        ];

        // Load one item snapshot and a SQL aggregate instead of hydrating every order item.
        return view('commerce-admin::orders.index', compact('orders', 'stats', 'search', 'status'));
    }

    public function create(): View
    {
        $variantModel = $this->model('variant', ProductVariant::class);
        $hasActiveVariants = $variantModel::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->exists();

        return view('commerce-admin::orders.create', compact('hasActiveVariants'));
    }

    public function searchVariants(SearchOrderVariantsRequest $request): JsonResponse
    {
        $variantModel = $this->model('variant', ProductVariant::class);
        $search = trim((string) $request->validated('q', ''));
        $selectedIds = collect($request->validated('ids', []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $baseQuery = fn () => $variantModel::query()
            ->with('product')
            ->where('is_active', true)
            ->whereHas('product', fn ($productQuery) => $productQuery->where('is_active', true));

        $selectedVariants = $selectedIds->isEmpty()
            ? collect()
            : $baseQuery()->whereKey($selectedIds)->get();

        $query = $baseQuery();
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $term = '%' . $search . '%';
                $builder->where('sku', 'like', $term)
                    ->orWhere('size', 'like', $term)
                    ->orWhere('color', 'like', $term)
                    ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', $term));
            });
        }

        $searchResults = $query->orderByDesc('updated_at')->orderByDesc('id')->limit(31)->get();
        $hasMore = $searchResults->count() > 30;
        $results = $selectedVariants->concat($searchResults->take(30))
            ->unique(fn ($variant) => $variant->getKey())
            ->values();
        $data = $results->map(fn ($variant): array => [
            'id' => $variant->getKey(),
            'label' => implode(' · ', array_values(array_filter([
                $variant->product?->name ?? 'محصول',
                ! in_array($variant->display_name, [$variant->sku, $variant->product?->name, 'Variant'], true) ? $variant->display_name : null,
                $variant->sku,
            ]))),
            'price' => (float) $variant->effective_price,
            'stock' => (int) $variant->stock,
        ])->values();

        return response()->json(['data' => $data, 'has_more' => $hasMore]);
    }

    public function store(StoreAdminOrderRequest $request): RedirectResponse
    {
        $orderModel = $this->model('order', Order::class);
        $itemModel = $this->model('order_item', OrderItem::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $inventoryModel = $this->model('inventory_movement', InventoryMovement::class);
        $userModel = $this->model('user', User::class);
        $paymentModel = $this->model('payment', Payment::class);

        $validated = $request->validated();

        $order = DB::transaction(function () use (
            $validated,
            $orderModel,
            $itemModel,
            $variantModel,
            $inventoryModel,
            $userModel,
            $paymentModel
        ) {
            $taxRate = max(0, (float) config('commerce-admin.tax_rate', 0));
            $email = isset($validated['customer_email'])
                ? Str::lower(trim((string) $validated['customer_email']))
                : null;
            $adminFlag = (string) config('commerce-admin.admin_flag_column', 'is_admin');
            $customer = $email
                ? $userModel::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where($adminFlag, false)
                    ->first()
                : null;

            $number = 'VS-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(6));
            $subtotalCents = 0;
            $taxCents = 0;
            $preparedItems = [];

            // Lock all requested variants in a stable order to reduce deadlocks,
            // and eager-load product/media once instead of issuing one query per line.
            $variantPrototype = new $variantModel();
            $variantIds = collect($validated['items'])
                ->pluck('variant_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $variants = $variantModel::query()
                ->with(['product', 'primaryGalleryMedia', 'product.primaryGalleryMedia'])
                ->whereKey($variantIds)
                ->orderBy($variantPrototype->getQualifiedKeyName())
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($variant): int => (int) $variant->getKey());

            foreach ($validated['items'] as $index => $input) {
                $variant = $variants->get((int) $input['variant_id']);

                if (! $variant) {
                    throw ValidationException::withMessages([
                        "items.$index.variant_id" => 'This product option no longer exists.',
                    ]);
                }

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

                $unitCents = (int) round(((float) $variant->effective_price) * 100);
                $lineCents = $unitCents * $quantity;
                $lineTaxCents = (bool) $variant->product->getAttribute('is_taxable')
                    ? (int) round($lineCents * $taxRate)
                    : 0;

                $subtotalCents += $lineCents;
                $taxCents += $lineTaxCents;
                $preparedItems[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitCents / 100,
                    'line_total' => $lineCents / 100,
                    'tax_amount' => $lineTaxCents / 100,
                    'image' => $variant->primaryGalleryMedia?->url
                        ?? $variant->product->primaryGalleryMedia?->url,
                ];
            }

            $shippingCents = (int) round(max(0, (float) $validated['shipping_cost']) * 100);
            $totalCents = $subtotalCents + $taxCents + $shippingCents;

            $order = $orderModel::query()->create([
                'user_id' => $customer?->getKey(),
                'address_id' => null,
                'order_number' => $number,
                'public_token' => (string) Str::uuid(),
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
                'subtotal' => $subtotalCents / 100,
                'discount' => 0,
                'shipping_cost' => $shippingCents / 100,
                'tax' => $taxCents / 100,
                'total' => $totalCents / 100,
                'placed_at' => now(),
            ]);

            $actorId = Auth::guard(config('commerce-admin.guard', 'web'))->id();

            foreach ($preparedItems as $line) {
                $variant = $line['variant'];
                $product = $variant->product;
                $after = (int) $variant->stock - $line['quantity'];

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

            // Manual orders start with an auditable pending payment, matching checkout.
            $paymentModel::query()->create([
                'order_id' => $order->getKey(),
                'gateway' => 'manual',
                'idempotency_key' => 'admin-order:' . $order->order_number,
                'amount' => $totalCents / 100,
                'status' => 'pending',
            ]);

            return $order;
        }, 3);

        return redirect()->route('commerce-admin.orders.show', $order->getKey())
            ->with('status', 'سفارش ثبت شد، موجودی به‌روزرسانی و پرداخت معلق ایجاد شد.');
    }

    public function show(int $order): View
    {
        $orderModel = $this->model('order', Order::class);
        $itemModel = $this->model('order_item', OrderItem::class);
        $model = $orderModel::query()->with(['user', 'payments'])->findOrFail($order);
        $items = $itemModel::query()->where('order_id', $model->getKey())->get();
        $availableStatuses = array_values(array_unique(array_merge(
            [$model->status],
            Order::STATUS_TRANSITIONS[$model->status] ?? []
        )));
        $paymentTransitions = [
            'pending' => ['paid', 'failed'],
            'failed' => ['pending', 'paid'],
            'paid' => ['refunded'],
            'refunded' => [],
        ];
        $availablePaymentStatuses = array_values(array_unique(array_merge(
            [$model->payment_status],
            $paymentTransitions[$model->payment_status] ?? []
        )));

        return view('commerce-admin::orders.show', compact(
            'model', 'items', 'availableStatuses', 'availablePaymentStatuses'
        ));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $order, OrderService $orderService): RedirectResponse
    {
        $orderModel = $this->model('order', Order::class);
        $validated = $request->validated();

        $record = $orderModel::query()->findOrFail($order);
        if (! $record instanceof Order) {
            throw ValidationException::withMessages([
                'status' => 'The configured order model must extend the application Order model to use the shared order workflow.',
            ]);
        }

        $orderService->updateStatus(
            $record,
            $validated['status'],
            (string) $record->payment_status,
            $record->customer_note,
            $record->tracking_code
        );

        return redirect()->route('commerce-admin.orders.show', $order)
            ->with('status', 'وضعیت سفارش ذخیره شد.');
    }

    public function updatePayment(UpdateOrderPaymentRequest $request, int $order, PaymentService $paymentService): RedirectResponse
    {
        $orderModel = $this->model('order', Order::class);
        $validated = $request->validated();

        $record = $orderModel::query()->findOrFail($order);
        if (! $record instanceof Order) {
            throw ValidationException::withMessages([
                'payment_status' => 'The configured order model must extend the application Order model to use shared payment accounting.',
            ]);
        }

        $paymentService->matchStatus($record, $validated['payment_status']);

        return redirect()->route('commerce-admin.orders.show', $order)
            ->with('status', 'وضعیت پرداخت و سوابق مالی از مسیر سرویس مشترک به‌روزرسانی شد.');
    }
}
