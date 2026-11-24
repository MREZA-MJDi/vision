
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CategoryController
{
    public function __construct(
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): View
    {
        $model = $this->categories->model();

        $search = mb_substr(
            trim($request->string('q')->toString()),
            0,
            120
        );

        $status = $request->string('status', 'all')->toString();

        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $items = $model::query()
            ->with(['parent', 'coverMedia'])
            ->withCount(['products', 'children'])
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                )
            )
            ->when(
                $status !== 'all',
                fn ($query) => $query->where(
                    'is_active',
                    $status === 'active'
                )
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate((int) config(
                'commerce-admin.items_per_page',
                10
            ))
            ->withQueryString();

        $stats = [
            'all' => $model::query()->count(),
            'active' => $model::query()
                ->where('is_active', true)->count(),
            'used' => $model::query()->whereHas('products')->count(),
        ];

        return view('commerce-admin::categories.index', compact(
            'items',
            'search',
            'status',
            'stats',
        ));
    }

    public function create(): View
    {
        $model = $this->categories->model();

        return view('commerce-admin::categories.create', [
            'record' => new $model(),
            'parents' => $this->parents(),
        ]);
    }

    public function store(
        SaveCategoryRequest $request
    ): RedirectResponse {
        $model = $this->categories->model();

        $this->categories->save(
            new $model(),
            $request->validated(),
            $request->file('cover_image')
        );

        return redirect()
            ->route('commerce-admin.categories.index')
            ->with('status', 'دسته‌بندی با موفقیت ایجاد شد.');
    }

    public function edit(int $category): View
    {
        $model = $this->categories->model();

        return view('commerce-admin::categories.edit', [
            'record' => $model::query()
                ->with('coverMedia')
                ->findOrFail($category),

            'parents' => $this->parents($category),
        ]);
    }

    public function update(
        SaveCategoryRequest $request,
        int $category
    ): RedirectResponse {
        $model = $this->categories->model();

        $this->categories->save(
            $model::query()->findOrFail($category),
            $request->validated(),
            $request->file('cover_image')
        );

        return redirect()
            ->route('commerce-admin.categories.index')
            ->with('status', 'تغییرات دسته‌بندی ذخیره شد.');
    }

    public function destroy(int $category): RedirectResponse
    {
        $model = $this->categories->model();

        $deleted = $this->categories->destroy(
            $model::query()->findOrFail($category)
        );

        return redirect()
            ->route('commerce-admin.categories.index')
            ->with(
                'status',
                $deleted
                    ? 'دسته‌بندی حذف شد.'
                    : 'دسته‌بندی دارای وابستگی است و غیرفعال شد.'
            );
    }

    private function parents(?int $except = null)
    {
        $model = $this->categories->model();

        return $model::query()
            ->when(
                $except !== null,
                fn ($query) => $query->where('id', '<>', $except)
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }
}
