<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthenticatedUserRedirector
{
    /**
     * Keep post-authentication routing centralized so every login entry point
     * applies the same server-side authorization decision.
     */
    public function hasAdminAccess(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('access-commerce-admin');
    }

    public function forExistingSession(Authenticatable $user): RedirectResponse
    {
        return $this->hasAdminAccess($user)
            ? redirect()->route('commerce-admin.dashboard')
            : redirect()->route('account.show');
    }

    public function afterLogin(Request $request, Authenticatable $user): RedirectResponse
    {
        if ($this->hasAdminAccess($user)) {
            // Admin navigation is role-specific; discard a stale customer/admin
            // intended URL rather than redirecting an admin into a random page.
            $request->session()->forget('url.intended');

            return redirect()->route('commerce-admin.dashboard');
        }

        return $this->safeCustomerRedirect($request);
    }

    /**
     * Registration only creates customer accounts, but a previous attempt to
     * open /admin may have left that protected URL in the session as intended.
     */
    public function afterCustomerRegistration(Request $request): RedirectResponse
    {
        return $this->safeCustomerRedirect($request);
    }

    private function safeCustomerRedirect(Request $request): RedirectResponse
    {
        $intended = $request->session()->get('url.intended');
        $intendedPath = is_string($intended) ? parse_url($intended, PHP_URL_PATH) : null;

        if (is_string($intendedPath)
            && ($intendedPath === '/admin' || str_starts_with($intendedPath, '/admin/'))) {
            $request->session()->forget('url.intended');

            return redirect()->route('account.show');
        }

        return redirect()->intended(route('account.show'));
    }
}
