<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\HeroService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $quality = in_array($request->query('quality'), [
            'missing-seo',
            'missing-image',
            'missing-variant',
        ], true) ? $request->query('quality') : null;

        $products = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'primaryActiveVariant',
                'primaryGalleryMedia',
            ])
            ->withCount('variants')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->input('q'));

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($variantQuery) use ($search) {
                            $variantQuery->where(
                                'sku',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where(
                    'category_id',
                    $request->input('category_id')
                )
            )
            ->when(
                $request->filled('brand_id'),
                fn ($query) => $query->where(
                    'brand_id',
                    $request->input('brand_id')
                )
            )
            ->when(
                $request->input('stock') === 'low',
                fn ($query) => $query->lowStock()
            )
            ->when($quality === 'missing-seo', fn ($query) => $query
                ->where('is_active', true)
                ->where(fn ($seo) => $seo
                    ->whereNull('meta_title')->orWhereRaw("TRIM(COALESCE(meta_title, '')) = ''")
                    ->orWhereNull('meta_description')->orWhereRaw("TRIM(COALESCE(meta_description, '')) = ''")
                ))
            ->when($quality === 'missing-image', fn ($query) => $query
                ->where('is_active', true)
                ->whereDoesntHave('galleryMedia'))
            ->when($quality === 'missing-variant', fn ($query) => $query
                ->where('is_active', true)
                ->whereDoesntHave('activeVariants'))
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    match ($request->input('status')) {
                        'active' => $query->where('is_active', true),

                        'inactive' => $query->where('is_active', false),

                        'featured' => $query
                            ->where('is_active', true)
                            ->where('is_featured', true),

                        default => null,
                    };
                }
            )
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'quality' => $quality,
            'categories' => $this->categories(),
            'brands' => $this->brands(),
        ]);
    }

    public function toggleHero(
        Product $product,
        HeroService $hero
    ): RedirectResponse {
        try {
            $isHero = $hero->toggle($product);

            return back()->with(
                'success',
                $isHero
                    ? 'محصول به Hero صفحه اصلی اضافه شد.'
                    : 'محصول از Hero صفحه اصلی حذف شد.'
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'hero_limit') {
                return back()->with(
                    'error',
                    'حداکثر ' . HeroService::MAX_SLIDES . ' محصول می‌تواند همزمان در Hero صفحه اصلی باشد.'
                );
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product(),
            'variant' => new ProductVariant(),
            'categories' => $this->categories(),
            'brands' => $this->brands(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreProductRequest $request,
        MediaService $media
    ): RedirectResponse {
        try {
            $data = $request->validated();

            $product = DB::transaction(function () use (
                $data,
                $request,
                $media
            ) {
                $product = Product::create(
                    $this->productData($data)
                );

                $product->variants()->create([
                    'sku' => $data['sku'] ?? null,
                    'size' => $data['size'] ?? null,
                    'color' => $data['color'] ?? null,
                    'color_code' => $data['color_code'] ?? null,
                    'price' => $data['price'],
                    'sale_price' => $data['sale_price'] ?? null,
                    'stock' => (int) ($data['stock'] ?? 0),
                    'low_stock_threshold' => (int) (
                        $data['low_stock_threshold'] ?? 5
                    ),
                    'is_active' => (bool) (
                        $data['is_active'] ?? true
                    ),
                ]);

                if ($request->hasFile('image_file')) {
                    $media->attach(
                        $product,
                        'gallery',
                        $request->file('image_file'),
                        'products',
                        $product->name
                    );
                }

                return $product;
            });

            return redirect()
                ->route('admin.products.edit', $product)
                ->with(
                    'success',
                    'محصول با موفقیت ایجاد شد.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'ایجاد محصول انجام نشد.'
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(Product $product): View
    {
        $product->load([
            'variants',
            'galleryMedia',
        ]);

        return view('admin.products.edit', [
            'product' => $product,
            'variant' => $product->variants->first(),
            'categories' => $this->categories(),
            'brands' => $this->brands(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateProductRequest $request,
        Product $product,
        MediaService $media,
        HeroService $hero
    ): RedirectResponse {
        try {
            $data = $request->validated();

            DB::transaction(function () use (
                $data,
                $request,
                $product,
                $media
            ) {
                /*
                |--------------------------------------------------------------------------
                | PRODUCT
                |--------------------------------------------------------------------------
                */

                $product->update(
                    $this->productData(
                        $data,
                        $product
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | MAIN VARIANT
                |--------------------------------------------------------------------------
                */

                $variant = $product->variants()
                    ->lockForUpdate()
                    ->orderBy('id')
                    ->first();

                if (!$variant) {
                    $variant = $product->variants()->create([
                        'sku' => $data['sku'] ?? null,
                        'size' => $data['size'] ?? null,
                        'color' => $data['color'] ?? null,
                        'color_code' => $data['color_code'] ?? null,
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'] ?? null,
                            'stock' => (int) ($data['stock'] ?? 0),
                        'low_stock_threshold' => (int) (
                            $data['low_stock_threshold'] ?? 5
                        ),
                        'is_active' => (bool) (
                            $data['is_active'] ?? true
                        ),
                    ]);
                } else {
                    $variant->update([
                        'sku' => $data['sku'] ?? null,
                        'size' => $data['size'] ?? null,
                        'color' => $data['color'] ?? null,
                        'color_code' => $data['color_code'] ?? null,
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'] ?? null,
                        'stock' => (int) ($data['stock'] ?? 0),
                        'low_stock_threshold' => (int) (
                            $data['low_stock_threshold'] ?? 5
                        ),
                        'is_active' => (bool) (
                            $data['is_active'] ?? true
                        ),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | IMAGE
                |--------------------------------------------------------------------------
                */

                if ($request->hasFile('image_file')) {
                    $media->replace(
                        $product,
                        'gallery',
                        $request->file('image_file'),
                        'products',
                        $product->name
                    );
                }
            });

            if ($product->is_hero) {
                $hero->invalidate();
            }

            return redirect()
                ->route('admin.products.index')
                ->with(
                    'success',
                    'محصول با موفقیت ویرایش شد.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'ویرایش محصول انجام نشد.'
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Product $product,
        MediaService $media,
        HeroService $hero
    ): RedirectResponse {
        try {
            DB::transaction(function () use (
                $product,
                $media
            ) {
                /*
                | Remove product gallery
                */

                $media->removeCollection(
                    $product,
                    'gallery'
                );

                /*
                | Deactivate variants
                */

                $product->variants()->update([
                    'is_active' => false,
                ]);

                /*
                | Soft delete product
                */

                $product->delete();
            });

            if ($product->is_hero) {
                $hero->invalidate();
            }

            return redirect()
                ->route('admin.products.index')
                ->with(
                    'success',
                    'محصول با موفقیت حذف شد.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'حذف محصول انجام نشد.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT DATA
    |--------------------------------------------------------------------------
    */

    private function productData(
        array $data,
        ?Product $product = null,
        ?string $generatedSlug = null
    ): array {
        $attributes = null;

        if (!empty($data['attributes_json'])) {

            $decoded = json_decode(
                $data['attributes_json'],
                true
            );

            if (is_array($decoded)) {
                $attributes = $decoded;
            }
        }

        return [
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'] ?? null,

            'name' => $data['name'],

            'slug' =>
                $data['slug']
                    ?: $generatedSlug
                    ?: $product?->slug
                        ?: $this->generateSlug($data['name']),

            'short_description' =>
                $data['short_description'] ?? null,

            'description' =>
                $data['description'] ?? null,

            'attributes' => $attributes,

            'is_active' =>
                (bool) ($data['is_active'] ?? false),

            'is_featured' =>
                (bool) ($data['is_featured'] ?? false),

            'sort_order' =>
                (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'product';

        $slug = $base;
        $suffix = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORIES
    |--------------------------------------------------------------------------
    */

    private function categories()
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | BRANDS
    |--------------------------------------------------------------------------
    */

    private function brands()
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
