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
            // Do not honor a customer-area intended URL for an administrator.
            return redirect()->route('commerce-admin.dashboard');
        }

        return redirect()->intended(route('account.show'));
    }
}
