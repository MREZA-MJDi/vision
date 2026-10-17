<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\SeoDefaults;
use App\Services\HeroService;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Admin\BulkArchiveProductsRequest;
use App\Http\Requests\Admin\SaveProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController
{
    private function model(string $key, string $fallback): string
    {
        return (string) config('commerce-admin.models.' . $key, $fallback);
    }

    private function formData(): array
    {
        $categoryModel = $this->model('category', Category::class);
        $brandModel = $this->model('brand', Brand::class);

        return [
            'categories' => $categoryModel::query()->orderBy('name')->get(),
            'brands' => $brandModel::query()->orderBy('name')->get(),
        ];
    }

    public function index(Request $request): View
    {
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $orderModel = $this->model('order', Order::class);
        $orderItemModel = $this->model('order_item', OrderItem::class);
        $userModel = $this->model('user', User::class);

        $query = $productModel::query()
            ->with(['category', 'brand', 'variants.primaryGalleryMedia', 'galleryMedia'])
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews');

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'draft', 'inactive', 'low_stock', 'sale'], true)) {
            $status = 'all';
        }
        $categoryId = $request->integer('category_id') ?: null;

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery
                        ->where('sku', 'like', '%' . $search . '%'));
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'draft' || $status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'low_stock') {
            $query->whereHas('variants', fn ($variants) => $variants
                ->where('is_active', true)
                ->whereColumn('stock', '<=', 'low_stock_threshold'));
        } elseif ($status === 'sale') {
            $query->whereHas('variants', fn ($variants) => $variants
                ->whereNotNull('sale_price')->where('sale_price', '>=', 0)->whereColumn('sale_price', '<', 'price'));
        }

        $products = $query->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'orders' => $orderModel::query()->whereNotIn('status', ['cancelled', 'returned'])->count(),
            'sales' => $orderItemModel::query()->whereHas('order', fn ($query) => $query
                ->where('payment_status', 'paid')->whereNotIn('status', ['cancelled', 'returned']))->sum('line_total'),
            'customers' => $userModel::query()->where(config('commerce-admin.admin_flag_column', 'is_admin'), false)->count(),
            'discount_total' => $orderModel::query()->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])->sum('discount'),
            'discounted' => $variantModel::query()->whereNotNull('sale_price')
                ->where('sale_price', '>=', 0)->whereColumn('sale_price', '<', 'price')->count(),
        ];

        $categoryModel = $this->model('category', Category::class);
        $categories = $categoryModel::query()->orderBy('name')->get();

        return view('commerce-admin::products.index', compact(
            'products', 'stats', 'categories', 'search', 'status', 'categoryId'
        ));
    }

    public function create(): View
    {
        return view('commerce-admin::products.form', array_merge(
            $this->formData(),
            ['product' => null, 'variants' => collect([(object) [
                'id' => null, 'sku' => '', 'size' => '', 'color' => '',
                'color_code' => '#171717', 'price' => null, 'sale_price' => null, 'stock' => 0,
                'low_stock_threshold' => 5, 'is_active' => true,
            ]])]
        ));
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $product = new $productModel();

        return $this->save($request, $product, false);
    }

    public function show(int $product): View
    {
        $productModel = $this->model('product', Product::class);
        $orderItemModel = $this->model('order_item', OrderItem::class);
        $model = $productModel::query()
            ->with(['category', 'brand', 'variants.primaryGalleryMedia', 'galleryMedia', 'primaryGalleryMedia'])
            ->withAvg('approvedReviews as approved_reviews_avg_rating', 'rating')
            ->withCount('approvedReviews')
            ->findOrFail($product);

        $sales = $orderItemModel::query()
            ->where('product_id', $model->getKey())
            ->whereHas('order', fn ($query) => $query
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned']));
        $unitsSold = (clone $sales)->sum('quantity');
        $grossSales = (clone $sales)->sum('line_total');
        $ordersCount = (clone $sales)->distinct('order_id')->count('order_id');
        $stock = $model->variants->where('is_active', true)->sum('stock');

        return view('commerce-admin::products.show', compact(
            'product', 'model', 'unitsSold', 'grossSales', 'ordersCount', 'stock'
        ));
    }

    public function edit(int $product): View
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()
            ->with(['category', 'brand', 'variants.galleryMedia', 'variants.primaryGalleryMedia', 'galleryMedia', 'primaryGalleryMedia'])
            ->findOrFail($product);

        $variants = $model->variants->values();
        if ($variants->isEmpty()) {
            $variants = collect([(object) [
                'id' => null, 'sku' => '', 'size' => '', 'color' => '',
                'color_code' => '#171717', 'price' => null, 'sale_price' => null, 'stock' => 0,
                'low_stock_threshold' => 5, 'is_active' => true,
            ]]);
        }

        return view('commerce-admin::products.form', array_merge(
            $this->formData(),
            ['product' => $model, 'variants' => $variants]
        ));
    }

    public function update(SaveProductRequest $request, int $product): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()->findOrFail($product);

        return $this->save($request, $model, true);
    }

    private function save(SaveProductRequest $request, object $product, bool $updating): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $inventoryModel = $this->model('inventory_movement', InventoryMovement::class);
        $validated = $request->validated();
        $requestedHeroState = (bool) ($validated['is_hero'] ?? false);
        $heroSelectionChanged = $requestedHeroState !== (bool) $product->is_hero;
        $serializeHeroSelection = $requestedHeroState || $heroSelectionChanged;

        $mediaDisk = (string) config('commerce-admin.media_disk', 'public');
        $storedImagePaths = [];
        $obsoleteImagePaths = [];

        try {
            $persist = function () use ($validated, $product, $productModel, $updating, $variantModel, $inventoryModel, $request, $mediaDisk, $requestedHeroState, &$storedImagePaths, &$obsoleteImagePaths): void {
                DB::transaction(function () use ($validated, $product, $productModel, $updating, $variantModel, $inventoryModel, $request, $mediaDisk, $requestedHeroState, &$storedImagePaths, &$obsoleteImagePaths): void {
            if ($requestedHeroState) {
                $heroProducts = $productModel::query()->where('is_hero', true);

                if ($product->exists) {
                    $heroProducts->where($product->getKeyName(), '<>', $product->getKey());
                }

                if ($heroProducts->count() >= HeroService::MAX_SLIDES) {
                    throw ValidationException::withMessages([
                        'is_hero' => 'حداکثر ۱۰ محصول می‌تواند در هِروی صفحهٔ اصلی باشد؛ ابتدا یک محصول دیگر را از هِرو خارج کن.',
                    ]);
                }
            }

            $requestedSlug = trim((string) ($validated['slug'] ?? ''));
            $slug = $requestedSlug !== '' ? $requestedSlug : Str::slug($validated['name']);

            if ($slug === '') {
                $slug = 'product';
            }

            if ($requestedSlug === '') {
                $baseSlug = $slug;
                $suffix = 2;
                $slugTaken = static function (string $candidate) use ($productModel, $product): bool {
                    return $productModel::withTrashed()
                        ->where('slug', $candidate)
                        ->when(
                            $product->exists,
                            fn ($query) => $query->where($product->getKeyName(), '<>', $product->getKey())
                        )
                        ->exists();
                };

                while ($slugTaken($slug)) {
                    $slug = $baseSlug . '-' . $suffix++;
                }
            }

            $product->fill([
                'name' => $validated['name'],
                'slug' => $slug,
                'category_id' => $validated['category_id'] ?? null,
                'brand_id' => $validated['brand_id'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'attributes' => collect($validated['attributes'] ?? [])
                    ->filter(fn ($attribute) => is_array($attribute)
                        && trim((string) ($attribute['key'] ?? '')) !== ''
                        && trim((string) ($attribute['value'] ?? '')) !== '')
                    ->mapWithKeys(fn ($attribute) => [
                        trim((string) $attribute['key']) => trim((string) $attribute['value']),
                    ])
                    ->all() ?: null,
                'meta_title' => SeoDefaults::title((string) $validated['name'], $validated['meta_title'] ?? null),
                'meta_description' => SeoDefaults::description((string) $validated['name'], $validated['meta_description'] ?? null, [
                    $validated['short_description'] ?? null,
                    $validated['description'] ?? null,
                ]),
                'is_active' => ($validated['status'] ?? 'draft') === 'active',
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'is_hero' => (bool) ($validated['is_hero'] ?? false),
                'is_taxable' => (bool) ($validated['is_taxable'] ?? false),
            ]);
            $product->save();

            // Hero products form a bounded playlist, ordered by their normal
            // catalog sort order. Editors can therefore publish more than one
            // real product without a second, duplicate hero-content system.

            $keepIds = [];
            $seenSkus = [];
            $variantPosition = 0;
            foreach ($validated['variants'] as $index => $input) {
                $variantId = $input['id'] ?? null;
                $sku = trim((string) ($input['sku'] ?? ''));

                // product_variants.sku is required and unique in the database.
                // When the form leaves it blank, generate a stable-format SKU
                // instead of attempting an invalid NULL insert.
                if ($sku === '') {
                    do {
                        $sku = 'VIS-' . Str::upper(Str::random(10));
                    } while (
                        in_array($sku, $seenSkus, true)
                        || $variantModel::withTrashed()->where('sku', $sku)->exists()
                    );
                }

                if (in_array($sku, $seenSkus, true)) {
                    throw ValidationException::withMessages([
                        "variants.$index.sku" => 'Each variant must have a different SKU.',
                    ]);
                }
                {
                    $exists = $variantModel::withTrashed()->where('sku', $sku)
                        ->when($variantId, fn ($q) => $q->where('id', '<>', $variantId))->exists();
                    if ($exists) {
                        throw ValidationException::withMessages([
                            "variants.$index.sku" => 'This SKU is already used by another product variant.',
                        ]);
                    }
                    $seenSkus[] = $sku;
                }
                if ($variantId) {
                    $variant = $product->variants()->whereKey($variantId)->firstOrFail();
                } else {
                    $variant = $product->variants()->make();
                }

                $previousStock = $variant->exists ? (int) $variant->stock : 0;

                $variant->fill([
                    'sku' => $sku,
                    'size' => $input['size'] ?? null,
                    'color' => $input['color'] ?? null,
                    'color_code' => $input['color_code'] ?? null,
                    'price' => $input['price'],
                    'sale_price' => ($input['sale_price'] ?? '') !== '' ? $input['sale_price'] : null,
                    'stock' => (int) $input['stock'],
                    'low_stock_threshold' => (int) $input['low_stock_threshold'],
                    'is_active' => (bool) $input['is_active'],
                    'sort_order' => $variantPosition++,
                ]);
                $variant->save();
                $keepIds[] = $variant->getKey();

                $stockDelta = (int) $variant->stock - $previousStock;
                if ($stockDelta !== 0) {
                    $inventoryModel::query()->create([
                        'product_variant_id' => $variant->getKey(),
                        'type' => 'adjustment',
                        'quantity' => $stockDelta,
                        'stock_after' => (int) $variant->stock,
                        'reference_type' => $product->getMorphClass(),
                        'reference_id' => $product->getKey(),
                        'note' => $variantId
                            ? 'Stock adjusted from the product editor.'
                            : 'Initial stock set from the product editor.',
                        'created_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
                    ]);
                }

                // Support both the editor's single-image input and gallery uploads.
                // Handle each upload exactly once to avoid deleting the newly stored file
                // when the queued old-image cleanup runs after the transaction commits.
                $variantImages = array_values(array_filter(array_merge(
                    [$request->file("variants.{$index}.image")],
                    (array) $request->file("variants.{$index}.images", [])
                )));
                $removeVariantImage = (bool) ($input['remove_image'] ?? false);

                if ($variantImages !== [] || $removeVariantImage) {
                    // Delete old media rows inside the transaction; physical files are
                    // removed only after commit so rollback never destroys live images.
                    foreach ($variant->galleryMedia()->get() as $existingAsset) {
                        $normalizedPath = ltrim((string) $existingAsset->path, '/');
                        $isVersionedDemoAsset = str_starts_with($normalizedPath, 'demo-products/');

                        if ($existingAsset->path
                            && ! $isVersionedDemoAsset
                            && ! Str::startsWith($existingAsset->path, ['http://', 'https://'])) {
                            $obsoleteImagePaths[] = [$existingAsset->disk ?: $mediaDisk, $existingAsset->path];
                        }

                        $existingAsset->delete();
                    }
                }

                if ($variantImages !== []) {
                    $directory = trim((string) config('commerce-admin.media_directory', 'products'), '/')
                        . '/' . $product->getKey() . '/variants/' . $variant->getKey();

                    foreach ($variantImages as $imageOffset => $variantImage) {
                        $path = $variantImage->store($directory, $mediaDisk);
                        if (! is_string($path) || $path === '') {
                            throw new \RuntimeException('ذخیره‌سازی تصویر تنوع انجام نشد.');
                        }

                        $storedImagePaths[] = $path;
                        $variant->media()->create([
                            'collection' => 'gallery',
                            'disk' => $mediaDisk,
                            'path' => $path,
                            'original_name' => $variantImage->getClientOriginalName(),
                            'mime_type' => $variantImage->getMimeType(),
                            'size' => $variantImage->getSize(),
                            'alt_text' => trim($product->name . ($variant->color ? ' - ' . $variant->color : '')),
                            'sort_order' => $imageOffset,
                            'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
                        ]);
                    }
                }

            }

            if ($updating) {
                $product->variants()->whereNotIn('id', $keepIds)->get()->each(function ($variant): void {
                    // Soft delete keeps historical order-line references intact.
                    $variant->delete();
                });
            }

            if ($request->hasFile('images')) {
                $directory = trim((string) config('commerce-admin.media_directory', 'products'), '/')
                    . '/' . $product->getKey();
                $sortOrder = $product->galleryMedia()->count();

                foreach ($request->file('images', []) as $offset => $image) {
                    $path = $image->store($directory, $mediaDisk);

                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('ذخیره‌سازی تصویر محصول انجام نشد.');
                    }

                    $storedImagePaths[] = $path;
                    $product->media()->create([
                        'collection' => 'gallery',
                        'disk' => $mediaDisk,
                        'path' => $path,
                        'original_name' => $image->getClientOriginalName(),
                        'mime_type' => $image->getMimeType(),
                        'size' => $image->getSize(),
                        'alt_text' => $product->name,
                        'sort_order' => $sortOrder + $offset,
                        'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
                    ]);
                }
            }
                }, 1);
            };

            if ($serializeHeroSelection) {
                Cache::lock('store:home:hero:toggle', 120)->block(10, $persist);
            } else {
                $persist();
            }
        } catch (\Throwable $exception) {
            foreach ($storedImagePaths as $path) {
                Storage::disk($mediaDisk)->delete($path);
            }

            throw $exception;
        }

        foreach ($obsoleteImagePaths as [$disk, $path]) {
            Storage::disk($disk)->delete($path);
        }

        return redirect()->route('commerce-admin.products.show', $product->getKey())
            ->with('status', $updating ? 'Product changes saved.' : 'Product created.');
    }

    public function bulkArchive(BulkArchiveProductsRequest $request): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $ids = array_values(array_unique(array_map('intval', $request->validated('product_ids'))));

        $archivedCount = DB::transaction(function () use ($productModel, $ids): int {
            $products = $productModel::query()->whereKey($ids)->lockForUpdate()->get();

            if ($products->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'product_ids' => 'یک یا چند محصول انتخاب‌شده دیگر در دسترس نیستند؛ فهرست را تازه‌سازی کنید.',
                ]);
            }

            foreach ($products as $product) {
                $product->delete();
            }

            return $products->count();
        }, 3);

        return redirect()->route('commerce-admin.products.index')
            ->with('status', number_format($archivedCount) . ' محصول با موفقیت بایگانی شد.');
    }

    public function destroy(int $product): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()->findOrFail($product);
        $model->delete(); // Soft-delete; previous orders and their snapshots remain intact.

        return redirect()->route('commerce-admin.products.index')->with('status', 'Product archived.');
    }

    public function deleteMedia(int $product, int $media): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()->findOrFail($product);
        $asset = $model->media()->whereKey($media)->first();
        if (! $asset) {
            $variant = $model->variants()->withTrashed()
                ->whereHas('media', fn ($query) => $query->whereKey($media))
                ->firstOrFail();
            $asset = $variant->media()->whereKey($media)->firstOrFail();
        }

        $normalizedPath = ltrim((string) $asset->path, '/');
        $isVersionedDemoAsset = str_starts_with($normalizedPath, 'demo-products/');

        // Demo artwork is version-controlled seed input. Remove its database
        // attachment, but keep the file so a future opt-in seed can restore it.
        if ($asset->path && ! $isVersionedDemoAsset && ! preg_match('/^https?:\/\//i', $asset->path)) {
            Storage::disk($asset->disk ?: config('commerce-admin.media_disk', 'public'))->delete($asset->path);
        }

        $asset->delete();

        return redirect()->route('commerce-admin.products.edit', $model->getKey())
            ->with('status', 'Image removed.');
    }

public function deleteVariantMedia(int $product, int $variant, int $media): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()->findOrFail($product);
        $variantModel = $this->model('variant', ProductVariant::class);
        $variantRecord = $variantModel::withTrashed()
            ->where('product_id', $model->getKey())
            ->whereKey($variant)
            ->firstOrFail();
        $asset = $variantRecord->media()->whereKey($media)->firstOrFail();

        if ($asset->path
            && ! Str::startsWith($asset->path, ['http://', 'https://'])
            && ! str_starts_with(ltrim((string) $asset->path, '/'), 'demo-products/')) {
            Storage::disk($asset->disk ?: config('commerce-admin.media_disk', 'public'))->delete($asset->path);
        }

        $asset->delete();

        return redirect()->route('commerce-admin.products.edit', $model->getKey())
            ->with('status', 'Image removed.');
    }

}
