<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController
{
    public function create(): View|RedirectResponse
    {
        $guard = config('commerce-admin.guard', 'web');
        $user = Auth::guard($guard)->user();
        $adminColumn = config('commerce-admin.admin_flag_column', 'is_admin');

        if ($user && (bool) $user->getAttribute($adminColumn)) {
            return redirect()->route('commerce-admin.dashboard');
        }

        return view('commerce-admin::auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $guardName = config('commerce-admin.guard', 'web');
        $adminColumn = config('commerce-admin.admin_flag_column', 'is_admin');
        $email = Str::lower($validated['email']);
        $key = 'commerce-admin-login:' . $email . '|' . $request->ip();
        $maxAttempts = (int) config('commerce-admin.login_throttle_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'email' => 'Too many sign-in attempts. Please wait before trying again.',
            ]);
        }

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
            $adminColumn => true,
        ];

        if (! Auth::guard($guardName)->attempt($credentials, (bool) ($validated['remember'] ?? false))) {
            RateLimiter::hit($key, (int) config('commerce-admin.login_throttle_seconds', 60));

            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match an administrator account.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('commerce-admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard(config('commerce-admin.guard', 'web'))->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('commerce-admin.login')->with('status', 'You have been signed out.');
    }
}
