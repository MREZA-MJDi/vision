<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $model = $this->model();
        $record = new $model();
        $data = $this->validated($request, $record, false);

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

    public function update(Request $request, int $brand): RedirectResponse
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($brand);
        $data = $this->validated($request, $record, true);

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

        if ($record->products()->exists()) {
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

    private function validated(Request $request, object $record, bool $updating): array
    {
        $model = $this->model();
        $table = (new $model())->getTable();
        $id = $updating ? $record->getKey() : null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($table, 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'draft'])],
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
