<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
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

class CustomerAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return (bool) Auth::user()->getAttribute('is_admin')
                ? redirect()->route('commerce-admin.dashboard')
                : redirect()->route('account.show');
        }

        return view('store.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return (bool) Auth::user()->getAttribute('is_admin')
                ? redirect()->route('commerce-admin.dashboard')
                : redirect()->route('account.show');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $sessionId = $request->session()->getId();
        // Authenticate through the shared storefront login. The trusted role flag
        // determines the destination after credentials have been verified.
        $credentials = [
            'email' => Str::lower(trim($validated['email'])),
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, (bool) ($validated['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'email' => 'These customer credentials could not be verified.',
            ]);
        }

        $request->session()->regenerate();

        // Administrators use the same email/password form as customers, but
        // their server-side role sends them to the protected administration area.
        if ((bool) Auth::user()->getAttribute('is_admin')) {
            return redirect()->route('commerce-admin.dashboard');
        }

        $this->mergeGuestCart((int) Auth::id(), $sessionId);

        return redirect()->intended(route('account.show'));
    }

    public function registerForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return (bool) Auth::user()->getAttribute('is_admin')
                ? redirect()->route('commerce-admin.dashboard')
                : redirect()->route('account.show');
        }

        return view('store.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return (bool) Auth::user()->getAttribute('is_admin')
                ? redirect()->route('commerce-admin.dashboard')
                : redirect()->route('account.show');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $sessionId = $request->session()->getId();
        $user = User::query()->create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => isset($validated['phone']) && trim($validated['phone']) !== ''
                ? trim($validated['phone'])
                : null,
            'password' => $validated['password'],
        ]);
        // The database default is false; assign explicitly to make this
        // customer-only registration invariant clear and future-proof.
        $user->forceFill(['is_admin' => false])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $this->mergeGuestCart((int) $user->getKey(), $sessionId);

        return redirect()->intended(route('account.show'))
            ->with('status', 'Your account is ready.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.index')
            ->with('status', 'You have been signed out.');
    }

    private function mergeGuestCart(int $userId, string $sessionId): void
    {
        DB::transaction(function () use ($userId, $sessionId): void {
            $guestCart = Cart::query()
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->lockForUpdate()
                ->first();

            if (! $guestCart) {
                return;
            }

            $customerCart = Cart::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $customerCart) {
                $guestCart->user_id = $userId;
                $guestCart->session_id = null;
                $guestCart->last_activity_at = now();
                $guestCart->save();

                return;
            }

            $items = CartItem::query()
                ->where('cart_id', $guestCart->getKey())
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get();

            foreach ($items as $guestItem) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->whereKey($guestItem->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (! $variant || ! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
                    continue;
                }

                $target = CartItem::query()
                    ->where('cart_id', $customerCart->getKey())
                    ->where('product_variant_id', $variant->getKey())
                    ->lockForUpdate()
                    ->first();

                $mergedQuantity = min(
                    (int) $variant->stock,
                    (int) ($target?->quantity ?? 0) + (int) $guestItem->quantity
                );

                if ($mergedQuantity < 1) {
                    continue;
                }

                if ($target) {
                    $target->quantity = $mergedQuantity;
                    $target->save();
                } else {
                    CartItem::query()->create([
                        'cart_id' => $customerCart->getKey(),
                        'product_variant_id' => $variant->getKey(),
                        'quantity' => $mergedQuantity,
                    ]);
                }
            }

            $guestCart->delete();
        }, 3);
    }
}
