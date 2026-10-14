<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = (string) config('auth.defaults.guard', 'web');
        $user = Auth::guard($guard)->user();

        if (! $user) {
            // Fetch/XHR consumers need a real 401 response when the session expires;
            // redirecting them to an HTML login page breaks their JSON contract.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
            }

            // The public storefront login also authenticates administrators.
            // Keep the named admin-login route as a compatibility redirect.
            return redirect()->guest(route('commerce-admin.login'));
        }

        Gate::forUser($user)->authorize('access-commerce-admin');

        return $next($request);
    }
}
