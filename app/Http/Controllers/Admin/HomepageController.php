<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HomepageContent;
use App\Services\HeroService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class HomepageController extends Controller
{
    public function index(HomepageContent $home, HeroService $hero): View
    {
        return view('commerce-admin::homepage.index', $home->data($hero));
    }

    public function update(Request $request, string $slot): RedirectResponse
    {
        abort_unless(array_key_exists($slot, HomepageContent::TRUST_SLOTS), 404);
        $request->validate(['image' => ['required', 'image', 'mimes:webp', 'max:10240', 'dimensions:max_width=4096,max_height=4096']]);
        $path = $request->file('image')->storeAs('homepage/trust', $slot . '.webp', 'public');
        abort_unless($path, 500, 'ذخیره تصویر انجام نشد.');
        return back()->with('status', 'تصویر صفحه اصلی ذخیره شد.');
    }

    public function destroy(string $slot): RedirectResponse
    {
        abort_unless(array_key_exists($slot, HomepageContent::TRUST_SLOTS), 404);
        $disk = Storage::disk('public');
        $path = "homepage/trust/{$slot}.webp";
        if ($disk->exists($path)) {
            abort_unless($disk->delete($path), 500);
        }
        return back()->with('status', 'تصویر پیش‌فرض بازیابی شد.');
    }
}
