<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('store.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        DB::table('contact_messages')->insert([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('contact.index')->with('status', 'پیام شما ثبت شد. از طریق اطلاعات تماس ثبت‌شده با شما ارتباط می‌گیریم.');
    }
}
