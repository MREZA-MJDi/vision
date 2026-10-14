<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\SaveBrandRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController
{
    private function model(): string
    {
        return (string) config('commerce-admin.models.brand', Brand::class);
    }

    public function index(Request $request): View
    {
        $model = $this->model();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $query = $model::query()->withCount('products');

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('slug', 'like', '%' . $search . '%'));
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } else {
            $status = 'all';
        }

        $items = $query->orderBy('name')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'all' => $model::query()->count(),
            'active' => $model::query()->where('is_active', true)->count(),
            'used' => $model::query()->whereHas('products')->count(),
        ];

        return view('commerce-admin::catalog.taxonomy-index', [
            'items' => $items,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
            'type' => 'brands',
            'taxonomyTitle' => 'برند',
            'taxonomyPlural' => 'برندها',
            'routePrefix' => 'commerce-admin.brands',
            'showParent' => false,
        ]);
    }

    public function create(): View
    {
        $model = $this->model();

        return $this->formView(new $model(), false);
    }

    public function store(SaveBrandRequest $request): RedirectResponse
    {
        $model = $this->model();
        $record = new $model();
        $data = $request->validated();

        DB::transaction(function () use ($record, $data, $model): void {
            $record->fill([
                'name' => $data['name'],
                'slug' => $this->resolveSlug($model, $data['slug'] ?? '', $data['name']),
                'description' => $data['description'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'is_active' => ($data['status'] ?? 'active') === 'active',
            ]);
            $record->save();
        });

        return redirect()->route('commerce-admin.brands.index')
            ->with('status', 'برند با موفقیت ایجاد شد.');
    }

    public function edit(int $brand): View
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($brand);

        return $this->formView($record, true);
    }

    public function update(SaveBrandRequest $request, int $brand): RedirectResponse
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($brand);
        $data = $request->validated();

        DB::transaction(function () use ($record, $data, $model): void {
            $record->fill([
                'name' => $data['name'],
                'slug' => $this->resolveSlug($model, $data['slug'] ?? '', $data['name'], $record->getKey(), $record->slug),
                'description' => $data['description'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'is_active' => ($data['status'] ?? 'active') === 'active',
            ]);
            $record->save();
        });

        return redirect()->route('commerce-admin.brands.index')
            ->with('status', 'تغییرات برند ذخیره شد.');
    }

    public function destroy(int $brand): RedirectResponse
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($brand);

        if ($record->products()->withTrashed()->exists()) {
            $record->forceFill(['is_active' => false])->save();

            return redirect()->route('commerce-admin.brands.index')
                ->with('status', 'این برند به محصول متصل است؛ برای حفظ ارتباط‌ها غیرفعال شد.');
        }

        $record->delete();

        return redirect()->route('commerce-admin.brands.index')
            ->with('status', 'برند حذف شد.');
    }

    private function formView(object $record, bool $editing): View
    {
        return view('commerce-admin::catalog.taxonomy-form', [
            'record' => $record,
            'editing' => $editing,
            'type' => 'brands',
            'taxonomyTitle' => 'برند',
            'routePrefix' => 'commerce-admin.brands',
            'parents' => collect(),
            'showParent' => false,
        ]);
    }

    private function resolveSlug(string $model, string $requestedSlug, string $name, ?int $ignoreId = null, ?string $currentSlug = null): string
    {
        $requestedSlug = trim($requestedSlug);
        if ($requestedSlug === '' && $currentSlug !== null) {
            return $currentSlug;
        }

        $base = Str::slug($requestedSlug !== '' ? $requestedSlug : $name);
        if ($base === '') {
            $base = 'brand-' . Str::lower(Str::random(8));
        }

        $candidate = $base;
        $suffix = 2;
        while ($model::withTrashed()->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->where($query->getModel()->getKeyName(), '<>', $ignoreId))
            ->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }
}
