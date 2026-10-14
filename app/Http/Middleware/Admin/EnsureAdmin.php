<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('commerce-admin.guard', 'web');

        if (! Auth::guard($guard)->check()) {
            return redirect()->guest(route('commerce-admin.login'));
        }

        $user = Auth::guard($guard)->user();
        $flagColumn = config('commerce-admin.admin_flag_column', 'is_admin');

        if (! $user || ! (bool) $user->getAttribute($flagColumn)) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Administrator access is required.');
        }

        return $next($request);
    }
}
