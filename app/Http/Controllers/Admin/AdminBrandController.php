<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminBrandController extends Controller
{
    public function index(Request $request): View
    {
        $brands = Brand::query()
            ->with('logoMedia')
            ->withCount('products')
            ->when(
                $request->filled('q'),
                function ($query) use ($request) {
                    $search = $request->string('q')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('slug', 'like', '%' . $search . '%');
                    });
                }
            )
            ->when(
                $request->has('active') && $request->input('active') !== '',
                fn ($query) => $query->where(
                    'is_active',
                    $request->boolean('active')
                )
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.brands.create', [
            'brand' => new Brand(),
        ]);
    }

    public function store(
        StoreBrandRequest $request,
        MediaService $media
    ): RedirectResponse {
        try {
            $data = $request->validated();

            $brand = DB::transaction(function () use ($data, $request, $media) {
                $slug = filled($data['slug'] ?? null)
                    ? $data['slug']
                    : $this->generateUniqueSlug($data['name']);

                $brand = Brand::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                if ($request->hasFile('logo_file')) {
                    $media->attach(
                        $brand,
                        'logo',
                        $request->file('logo_file'),
                        'brands',
                        $brand->name
                    );
                }

                return $brand;
            });

            return redirect()
                ->route('admin.brands.edit', $brand)
                ->with('success', 'برند با موفقیت ایجاد شد.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'ایجاد برند انجام نشد.');
        }
    }

    public function edit(Brand $brand): View
    {
        $brand->load('logoMedia');

        return view('admin.brands.edit', compact('brand'));
    }

    public function update(
        UpdateBrandRequest $request,
        Brand $brand,
        MediaService $media
    ): RedirectResponse {
        try {
            $data = $request->validated();

            DB::transaction(function () use ($brand, $data, $request, $media) {
                $slug = filled($data['slug'] ?? null)
                    ? $data['slug']
                    : $this->generateUniqueSlug(
                        $data['name'],
                        $brand->getKey()
                    );

                $brand->update([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'is_active' => $request->boolean('is_active'),
                ]);

                if ($request->hasFile('logo_file')) {
                    $media->replace(
                        $brand,
                        'logo',
                        $request->file('logo_file'),
                        'brands',
                        $brand->name
                    );
                }
            });

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'برند با موفقیت به‌روزرسانی شد.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'به‌روزرسانی برند انجام نشد.');
        }
    }

    public function destroy(
        Brand $brand,
        MediaService $media
    ): RedirectResponse {
        try {
            if ($brand->products()->exists()) {
                return back()
                    ->with('error', 'این برند هنوز محصول دارد و قابل حذف نیست.');
            }

            DB::transaction(function () use ($brand, $media) {
                $media->removeCollection($brand, 'logo');

                $brand->delete();
            });

            return redirect()
                ->route('admin.brands.index')
                ->with('success', 'برند با موفقیت حذف شد.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->with('error', 'حذف برند انجام نشد.');
        }
    }

    private function generateUniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'brand';
        }

        $slug = $base;
        $counter = 2;

        while (
        Brand::query()
            ->where('slug', $slug)
            ->when(
                $ignoreId,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
