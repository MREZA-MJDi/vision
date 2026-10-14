<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Auth\AuthenticatedUserRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function create(AuthenticatedUserRedirector $redirector): View|RedirectResponse
    {
        if (Auth::check()) {
            return $redirector->forExistingSession(Auth::user());
        }

        return view('store.auth.login');
    }

    public function store(Request $request, AuthenticatedUserRedirector $redirector): RedirectResponse
    {
        if (Auth::check()) {
            return $redirector->forExistingSession(Auth::user());
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $key = 'storefront-login:' . hash('sha256', $email . '|' . $request->ip());
        $maxAttempts = max(1, (int) config('commerce-admin.login_throttle_attempts', 5));
        $decaySeconds = max(1, (int) config('commerce-admin.login_throttle_seconds', 60));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'email' => 'اطلاعات ورود تأیید نشد. لطفاً کمی بعد دوباره تلاش کن.',
            ]);
        }

        $sessionId = $request->session()->getId();
        $credentials = [
            'email' => $email,
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, (bool) ($validated['remember'] ?? false))) {
            RateLimiter::hit($key, $decaySeconds);

            throw ValidationException::withMessages([
                'email' => 'اطلاعات ورود صحیح نیست.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = Auth::user();
        if ($redirector->hasAdminAccess($user)) {
            return $redirector->afterLogin($request, $user);
        }

        $this->mergeGuestCart((int) Auth::id(), $sessionId);

        return $redirector->afterLogin($request, $user);
    }

    public function registerForm(AuthenticatedUserRedirector $redirector): View|RedirectResponse
    {
        if (Auth::check()) {
            return $redirector->forExistingSession(Auth::user());
        }

        return view('store.auth.register');
    }

    public function register(Request $request, AuthenticatedUserRedirector $redirector): RedirectResponse
    {
        if (Auth::check()) {
            return $redirector->forExistingSession(Auth::user());
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

        return $redirector->afterCustomerRegistration($request)
            ->with('status', 'حساب کاربری شما آماده است.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.index')
            ->with('status', 'با موفقیت از حساب خارج شدی.');
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
