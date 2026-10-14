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
        $guard = (string) config('commerce-admin.guard', 'web');
        $user = Auth::guard($guard)->user();

        if (! $user) {
            // The public storefront login also authenticates administrators.
            // Keep the named admin-login route as a compatibility redirect.
            return redirect()->guest(route('commerce-admin.login'));
        }

        Gate::forUser($user)->authorize('access-commerce-admin');

        return $next($request);
    }
}
