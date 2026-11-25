<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HomepageContent;
use App\Services\HeroService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
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
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:max_width=4096,max_height=4096']], [], ['image' => 'تصویر اعتمادسازی']);
        $file = $request->file('image');
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['image' => 'پشتیبانی GD و WebP باید روی سرور فعال باشد.']);
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (!$source) {
            throw ValidationException::withMessages(['image' => 'تصویر قابل خواندن نیست.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 1024 / max($width, $height));
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $output = imagecreatetruecolor($w, $h);
        abort_unless($output, 500, 'آماده‌سازی تصویر انجام نشد.');
        imagealphablending($output, false);
        imagesavealpha($output, true);
        imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
        imagecopyresampled($output, $source, 0, 0, 0, 0, $w, $h, $width, $height);
        ob_start();
        try {
            $encoded = imagewebp($output, null, 92);
        } finally {
            $bytes = ob_get_clean();
        }
        abort_unless($encoded && is_string($bytes) && str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP', 500, 'تبدیل تصویر انجام نشد.');

        // Keep the existing slot URL and replace the local public file atomically.
        $disk = Storage::disk('public');
        $disk->makeDirectory('homepage/trust');
        File::replace($disk->path("homepage/trust/{$slot}.webp"), $bytes, 0644);
        return redirect()->route('commerce-admin.homepage.index')->with('status', 'تصویر صفحه اصلی ذخیره شد.');
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
