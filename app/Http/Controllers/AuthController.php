<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('login');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! Auth::attempt([
            'phone' => $data['phone'],
            'password' => $data['password'],
            'is_admin' => true,
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'شماره موبایل یا رمز عبور نادرست است.'])
                ->with('error', 'ورود مدیر انجام نشد؛ اطلاعات حساب را بررسی کنید.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'با موفقیت وارد شدید.');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'از حساب خارج شدید.');
    }
}
