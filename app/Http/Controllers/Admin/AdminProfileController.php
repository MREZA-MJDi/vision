<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', [
            'admin' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password' => ['nullable', 'required_with:password', 'current_password:password'],
        ], [
            'current_password.required_with' => 'برای تغییر رمز، رمز فعلی را وارد کنید.',
            'current_password.current_password' => 'رمز فعلی صحیح نیست.',
        ]);

        $user->update([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
            ...(
                filled($data['password'] ?? null)
                    ? ['password' => $data['password']]
                    : []
            ),
        ]);

        return back()->with('success', 'پروفایل مدیر با موفقیت به‌روزرسانی شد.');
    }
}
