<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Support\SeoDefaults;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\SaveCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController
{
    private function model(): string
    {
        return (string) config('commerce-admin.models.category', Category::class);
    }

    public function index(Request $request): View
    {
        $model = $this->model();
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $query = $model::query()->with('parent')->withCount(['products', 'children']);

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

        $items = $query->orderBy('sort_order')->orderBy('name')
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
            'type' => 'categories',
            'taxonomyTitle' => 'دسته‌بندی',
            'taxonomyPlural' => 'دسته‌بندی‌ها',
            'routePrefix' => 'commerce-admin.categories',
            'showParent' => true,
        ]);
    }

    public function create(): View
    {
        $model = $this->model();

        return $this->formView(new $model(), false);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $model = $this->model();
        $record = new $model();
        $data = $request->validated();

        DB::transaction(function () use ($record, $data, $model): void {
            $record->fill([
                'name' => $data['name'],
                'slug' => $this->resolveSlug($model, $data['slug'] ?? '', $data['name']),
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'meta_title' => SeoDefaults::title((string) $data['name'], $data['meta_title'] ?? null),
                'meta_description' => SeoDefaults::description((string) $data['name'], $data['meta_description'] ?? null, $data['description'] ?? null),
                'is_active' => ($data['status'] ?? 'active') === 'active',
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
            $record->save();
        });

        return redirect()->route('commerce-admin.categories.index')
            ->with('status', 'دسته‌بندی با موفقیت ایجاد شد.');
    }

    public function edit(int $category): View
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($category);

        return $this->formView($record, true);
    }

    public function update(SaveCategoryRequest $request, int $category): RedirectResponse
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($category);
        $data = $request->validated();

        DB::transaction(function () use ($record, $data, $model): void {
            $record->fill([
                'name' => $data['name'],
                'slug' => $this->resolveSlug($model, $data['slug'] ?? '', $data['name'], $record->getKey(), $record->slug),
                'parent_id' => $data['parent_id'] ?? null,
                'description' => $data['description'] ?? null,
                'meta_title' => SeoDefaults::title((string) $data['name'], $data['meta_title'] ?? null),
                'meta_description' => SeoDefaults::description((string) $data['name'], $data['meta_description'] ?? null, $data['description'] ?? null),
                'is_active' => ($data['status'] ?? 'active') === 'active',
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
            $record->save();
        });

        return redirect()->route('commerce-admin.categories.index')
            ->with('status', 'تغییرات دسته‌بندی ذخیره شد.');
    }

    public function destroy(int $category): RedirectResponse
    {
        $model = $this->model();
        $record = $model::query()->findOrFail($category);

        if ($record->products()->withTrashed()->exists() || $record->children()->withTrashed()->exists()) {
            $record->forceFill(['is_active' => false])->save();

            return redirect()->route('commerce-admin.categories.index')
                ->with('status', 'این دسته‌بندی هنوز محصول یا زیر‌دسته دارد؛ برای حفظ ارتباط‌ها غیرفعال شد.');
        }

        $record->delete();

        return redirect()->route('commerce-admin.categories.index')
            ->with('status', 'دسته‌بندی حذف شد.');
    }

    private function formView(object $record, bool $editing): View
    {
        $model = $this->model();
        $parents = $model::query()
            ->when($record->getKey(), fn ($query) => $query->where($record->getKeyName(), '<>', $record->getKey()))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('commerce-admin::catalog.taxonomy-form', [
            'record' => $record,
            'editing' => $editing,
            'type' => 'categories',
            'taxonomyTitle' => 'دسته‌بندی',
            'routePrefix' => 'commerce-admin.categories',
            'parents' => $parents,
            'showParent' => true,
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
            $base = 'category-' . Str::lower(Str::random(8));
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
