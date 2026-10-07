<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with(['coverMedia', 'parent'])
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
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parentCategories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.categories.create', [
            'category' => new Category(),
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(
        StoreCategoryRequest $request,
        MediaService $media
    ): RedirectResponse {
        try {
            $data = $request->validated();

            $category = DB::transaction(function () use ($data, $request, $media) {
                $slug = filled($data['slug'] ?? null)
                    ? $data['slug']
                    : $this->generateUniqueSlug($data['name']);

                $category = Category::create([
                    'parent_id' => $data['parent_id'] ?? null,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'is_active' => $request->boolean('is_active', true),
                ]);

                if ($request->hasFile('image_file')) {
                    $media->attach(
                        $category,
                        'cover',
                        $request->file('image_file'),
                        'categories',
                        $category->name
                    );
                }

                return $category;
            });

            return redirect()
                ->route('admin.categories.edit', $category)
                ->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'ایجاد دسته‌بندی انجام نشد.');
        }
    }

    public function edit(Category $category): View
    {
        $category->load('coverMedia');

        $parentCategories = Category::query()
            ->whereKeyNot($category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.categories.edit', compact(
            'category',
            'parentCategories'
        ));
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        MediaService $media
    ): RedirectResponse {
        try {
            $data = $request->validated();

            if (
                !empty($data['parent_id']) &&
                $this->isDescendant(
                    $category,
                    (int) $data['parent_id']
                )
            ) {
                return back()
                    ->withInput()
                    ->with('error', 'این دسته‌بندی نمی‌تواند زیر‌دسته خودش یا یکی از زیر‌دسته‌هایش باشد.');
            }

            DB::transaction(function () use (
                $category,
                $data,
                $request,
                $media
            ) {
                $slug = filled($data['slug'] ?? null)
                    ? $data['slug']
                    : $this->generateUniqueSlug(
                        $data['name'],
                        $category->getKey()
                    );

                $category->update([
                    'parent_id' => $data['parent_id'] ?? null,
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                    'is_active' => $request->boolean('is_active'),
                ]);

                if ($request->hasFile('image_file')) {
                    $media->replace(
                        $category,
                        'cover',
                        $request->file('image_file'),
                        'categories',
                        $category->name
                    );
                }
            });

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'دسته‌بندی با موفقیت به‌روزرسانی شد.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'به‌روزرسانی دسته‌بندی انجام نشد.');
        }
    }

    public function destroy(
        Category $category,
        MediaService $media
    ): RedirectResponse {
        try {
            if (
                $category->products()->exists() ||
                $category->children()->exists()
            ) {
                return back()
                    ->with('error', 'این دسته‌بندی دارای محصول یا زیر‌دسته است و قابل حذف نیست.');
            }

            DB::transaction(function () use ($category, $media) {
                $media->removeCollection($category, 'cover');

                $category->delete();
            });

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'دسته‌بندی با موفقیت حذف شد.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->with('error', 'حذف دسته‌بندی انجام نشد.');
        }
    }

    private function generateUniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'category';
        }

        $slug = $base;
        $counter = 2;

        while (
        Category::query()
            ->where('slug', $slug)
            ->when(
                $ignoreId !== null,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function isDescendant(
        Category $category,
        int $parentId
    ): bool {
        $currentId = $parentId;

        while ($currentId) {
            if ($currentId === $category->getKey()) {
                return true;
            }

            $currentId = Category::query()
                ->whereKey($currentId)
                ->value('parent_id');
        }

        return false;
    }
}
