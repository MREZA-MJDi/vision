<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerCommerceController extends Controller
{
    /**
     * Resolve a cart for a signed-in customer or the current browser session.
     *
     * Admin sessions use a session-bound guest cart rather than attaching
     * customer purchases to an administrator account.
     */
    private function currentCart(): Cart
    {
        $user = Auth::user();
        $customerId = $user && ! (bool) $user->getAttribute('is_admin')
            ? (int) $user->getKey()
            : null;
        $sessionId = session()->getId();

        $identity = $customerId !== null
            ? ['user_id' => $customerId]
            : ['session_id' => $sessionId];

        $attributes = [
            'user_id' => $customerId,
            'session_id' => $customerId === null ? $sessionId : null,
            'last_activity_at' => now(),
        ];

        try {
            $cart = Cart::query()->firstOrCreate($identity, $attributes);
        } catch (QueryException $exception) {
            // Another request may have created the same cart between the read
            // and insert. Only suppress the race when the identity now exists.
            $cart = Cart::query()->where($identity)->first();
            if (! $cart) {
                throw $exception;
            }
        }

        $cart->forceFill(['last_activity_at' => now()])->save();

        return $cart;
    }

    private function loadCartItems(Cart $cart): EloquentCollection
    {
        return $cart->items()
            ->with([
                'productVariant.product',
                'productVariant.primaryGalleryMedia',
                'productVariant.product.primaryGalleryMedia',
            ])
            ->orderBy('id')
            ->get();
    }

    private function presentCart(EloquentCollection $items): array
    {
        $subtotalCents = 0;
        $taxCents = 0;
        $taxRate = max(0, (float) config('commerce-admin.tax_rate', 0));

        foreach ($items as $item) {
            $variant = $item->productVariant;
            $product = $variant?->product;

            if (! $variant || ! $product) {
                $item->setAttribute('checkout_available', false);
                $item->setAttribute('display_unit_price', 0);
                $item->setAttribute('display_line_total', 0);
                continue;
            }

            $unitCents = (int) round(((float) $variant->effective_price) * 100);
            $lineCents = $unitCents * max(0, (int) $item->quantity);
            $subtotalCents += $lineCents;

            if ((bool) $product->getAttribute('is_taxable')) {
                $taxCents += (int) round($lineCents * $taxRate);
            }

            $item->setAttribute(
                'checkout_available',
                (bool) $product->is_active
                    && (bool) $variant->is_active
                    && (int) $variant->stock >= (int) $item->quantity
            );
            $item->setAttribute('display_unit_price', $unitCents / 100);
            $item->setAttribute('display_line_total', $lineCents / 100);
        }

        $shippingCents = (int) round(max(0, (float) config('commerce-admin.shipping_flat_rate', 0)) * 100);

        return [
            'subtotal' => $subtotalCents / 100,
            'tax' => $taxCents / 100,
            'shipping' => $shippingCents / 100,
            'total' => ($subtotalCents + $taxCents + $shippingCents) / 100,
            'itemCount' => (int) $items->sum('quantity'),
            'allAvailable' => $items->isNotEmpty() && $items->every(
                fn (CartItem $item): bool => (bool) $item->getAttribute('checkout_available')
            ),
        ];
    }

    public function summary(): \Illuminate\Http\JsonResponse
    {
        $cart = $this->currentCart();

        return response()->json([
            'count' => (int) $cart->items()->sum('quantity'),
        ]);
    }

    public function index(): View
    {
        $cart = $this->currentCart();
        $items = $this->loadCartItems($cart);
        $summary = $this->presentCart($items);

        return view('store.cart', compact('items', 'summary'));
    }

    public function storeItem(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart = $this->currentCart();

        DB::transaction(function () use ($cart, $validated, $quantity): void {
            Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();

            $variant = ProductVariant::query()
                ->with('product')
                ->whereKey($validated['variant_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
                throw ValidationException::withMessages([
                    'variant_id' => 'This product option is no longer available.',
                ]);
            }

            $existing = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->where('product_variant_id', $variant->getKey())
                ->lockForUpdate()
                ->first();

            $nextQuantity = (int) ($existing?->quantity ?? 0) + $quantity;
            if ((int) $variant->stock < $nextQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only ' . (int) $variant->stock . ' unit(s) are currently available.',
                ]);
            }

            if ($existing) {
                $existing->quantity = $nextQuantity;
                $existing->save();
            } else {
                CartItem::query()->create([
                    'cart_id' => $cart->getKey(),
                    'product_variant_id' => $variant->getKey(),
                    'quantity' => $quantity,
                ]);
            }
        });

        $count = (int) $cart->items()->sum('quantity');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Added to your bag.',
                'count' => $count,
            ], 201);
        }

        return redirect()->route('cart.index')->with('status', 'Added to your bag.');
    }

    public function updateItem(Request $request, int $item): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $cart = $this->currentCart();

        DB::transaction(function () use ($cart, $item, $validated): void {
            Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();

            $cartItem = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->whereKey($item)
                ->lockForUpdate()
                ->firstOrFail();

            $variant = ProductVariant::query()
                ->with('product')
                ->whereKey($cartItem->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (! $variant || ! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
                throw ValidationException::withMessages([
                    'quantity' => 'This item is no longer available. Remove it from your bag.',
                ]);
            }

            $quantity = (int) $validated['quantity'];
            if ((int) $variant->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Only ' . (int) $variant->stock . ' unit(s) are currently available.',
                ]);
            }

            $cartItem->quantity = $quantity;
            $cartItem->save();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bag updated.',
                'count' => (int) $cart->items()->sum('quantity'),
            ]);
        }

        return redirect()->route('cart.index')->with('status', 'Bag updated.');
    }

    public function destroyItem(Request $request, int $item): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $cart = $this->currentCart();
        $deleted = CartItem::query()
            ->where('cart_id', $cart->getKey())
            ->whereKey($item)
            ->delete();

        abort_unless($deleted > 0, 404);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Item removed.',
                'count' => (int) $cart->items()->sum('quantity'),
            ]);
        }

        return redirect()->route('cart.index')->with('status', 'Item removed from your bag.');
    }

    public function checkout(): View|RedirectResponse
    {
        $cart = $this->currentCart();
        $items = $this->loadCartItems($cart);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your bag is empty.');
        }

        $summary = $this->presentCart($items);

        return view('store.checkout', compact('items', 'summary'));
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'shipping_province' => ['nullable', 'string', 'max:100'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(['cash_on_delivery', 'bank_transfer'])],
        ]);

        $cart = $this->currentCart();
        $customer = Auth::user();
        if ($customer && (bool) $customer->getAttribute('is_admin')) {
            $customer = null;
        }

        $order = DB::transaction(function () use ($validated, $cart, $customer): Order {
            Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();

            $cartItems = CartItem::query()
                ->where('cart_id', $cart->getKey())
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your bag is empty. Add a product before checking out.',
                ]);
            }

            $variantIds = $cartItems->pluck('product_variant_id')->unique()->sort()->values();
            $variants = ProductVariant::query()
                ->with(['product', 'primaryGalleryMedia', 'product.primaryGalleryMedia'])
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $taxRate = max(0, (float) config('commerce-admin.tax_rate', 0));
            $subtotalCents = 0;
            $taxCents = 0;
            $prepared = [];

            foreach ($cartItems as $cartItem) {
                $variant = $variants->get($cartItem->product_variant_id);
                $product = $variant?->product;
                $quantity = (int) $cartItem->quantity;

                if (! $variant || ! $product || ! $variant->is_active || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products are no longer available. Update your bag and try again.',
                    ]);
                }

                if ($quantity < 1 || (int) $variant->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => $product->name . ' no longer has enough stock for your requested quantity.',
                    ]);
                }

                $unitCents = (int) round(((float) ($variant->sale_price ?? $variant->price)) * 100);
                $lineCents = $unitCents * $quantity;
                $lineTaxCents = (bool) $product->getAttribute('is_taxable')
                    ? (int) round($lineCents * $taxRate)
                    : 0;

                $subtotalCents += $lineCents;
                $taxCents += $lineTaxCents;
                $prepared[] = [
                    'cart_item' => $cartItem,
                    'variant' => $variant,
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitCents / 100,
                    'line_total' => $lineCents / 100,
                    'tax_amount' => $lineTaxCents / 100,
                    'image' => $variant->primaryGalleryMedia?->url
                        ?? $product->primaryGalleryMedia?->url,
                ];
            }

            $shippingCents = (int) round(max(0, (float) config('commerce-admin.shipping_flat_rate', 0)) * 100);
            $totalCents = $subtotalCents + $taxCents + $shippingCents;
            $email = $validated['customer_email'] ?? null;
            $orderNumber = 'VS-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(6));
            $order = Order::query()->create([
                'user_id' => $customer?->getKey(),
                'address_id' => null,
                'order_number' => $orderNumber,
                'public_token' => (string) Str::uuid(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $email,
                'shipping_address' => $validated['shipping_address'],
                'shipping_province' => $validated['shipping_province'] ?? null,
                'shipping_city' => $validated['shipping_city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'customer_note' => $validated['customer_note'] ?? null,
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

            foreach ($prepared as $line) {
                /** @var ProductVariant $variant */
                $variant = $line['variant'];
                $after = (int) $variant->stock - $line['quantity'];

                OrderItem::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => $line['product']->getKey(),
                    'product_variant_id' => $variant->getKey(),
                    'product_name' => $line['product']->name,
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

                InventoryMovement::query()->create([
                    'product_variant_id' => $variant->getKey(),
                    'type' => 'sale',
                    'quantity' => -$line['quantity'],
                    'stock_after' => $after,
                    'reference_type' => $order->getMorphClass(),
                    'reference_id' => $order->getKey(),
                    'note' => 'Stock reserved for customer checkout ' . $order->order_number,
                    'created_by' => null,
                ]);
            }

            Payment::query()->create([
                'order_id' => $order->getKey(),
                'gateway' => 'manual',
                'idempotency_key' => 'checkout:' . $order->order_number,
                'amount' => $totalCents / 100,
                'status' => 'pending',
            ]);

            CartItem::query()->where('cart_id', $cart->getKey())->delete();

            return $order;
        }, 3);

        return redirect()
            ->route('checkout.complete', ['token' => $order->public_token])
            ->with('status', 'Your order has been placed.');
    }

    public function complete(string $token): View
    {
        $order = Order::query()
            ->with('items')
            ->where('public_token', $token)
            ->firstOrFail();

        return view('store.checkout-complete', compact('order'));
    }
}
