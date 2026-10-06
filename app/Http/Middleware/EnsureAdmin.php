<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless(
            $user->isAdmin(),
            403,
            'این بخش فقط برای مدیر فروشگاه است.'
        );

        return $next($request);
    }
}
