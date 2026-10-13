<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        $query = $productModel::query()->with(['category', 'brand', 'variants', 'galleryMedia']);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $categoryId = $request->integer('category_id') ?: null;

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery
                        ->where('sku', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%'));
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
                ->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price'));
        }

        $products = $query->orderByDesc('created_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $stats = [
            'revenue' => $orderModel::query()->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])->sum('total'),
            'orders' => $orderModel::query()->count(),
            'products' => $productModel::query()->count(),
            'low_stock' => $variantModel::query()->where('is_active', true)
                ->whereColumn('stock', '<=', 'low_stock_threshold')->count(),
            'discounted' => $variantModel::query()->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price')->count(),
            'units_sold' => $orderItemModel::query()->sum('quantity'),
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
                'id' => null, 'sku' => '', 'barcode' => '', 'size' => '', 'color' => '',
                'color_code' => '#171717', 'price' => null, 'sale_price' => null, 'stock' => 0,
                'low_stock_threshold' => 5, 'is_active' => true,
            ]])]
        ));
    }

    public function store(Request $request): RedirectResponse
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
            ->with(['category', 'brand', 'variants', 'galleryMedia'])
            ->findOrFail($product);

        $sales = $orderItemModel::query()->where('product_id', $model->getKey());
        $unitsSold = (clone $sales)->sum('quantity');
        $grossSales = (clone $sales)->sum('line_total');
        $ordersCount = (clone $sales)->distinct('order_id')->count('order_id');
        $stock = $model->variants->sum('stock');

        return view('commerce-admin::products.show', compact(
            'product', 'model', 'unitsSold', 'grossSales', 'ordersCount', 'stock'
        ));
    }

    public function edit(int $product): View
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()
            ->with(['category', 'brand', 'variants', 'galleryMedia'])
            ->findOrFail($product);

        $variants = $model->variants->values();
        if ($variants->isEmpty()) {
            $variants = collect([(object) [
                'id' => null, 'sku' => '', 'barcode' => '', 'size' => '', 'color' => '',
                'color_code' => '#171717', 'price' => null, 'sale_price' => null, 'stock' => 0,
                'low_stock_threshold' => 5, 'is_active' => true,
            ]]);
        }

        return view('commerce-admin::products.form', array_merge(
            $this->formData(),
            ['product' => $model, 'variants' => $variants]
        ));
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $model = $productModel::query()->findOrFail($product);

        return $this->save($request, $model, true);
    }

    private function save(Request $request, object $product, bool $updating): RedirectResponse
    {
        $productModel = $this->model('product', Product::class);
        $variantModel = $this->model('variant', ProductVariant::class);
        $productTable = (new $productModel())->getTable();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($productTable, 'slug')->ignore($product->getKey())],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_hero' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'draft'])],
            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.color_code' => ['nullable', 'string', 'max:20'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['required', 'boolean'],
            'images' => ['sometimes', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];

        $validated = $request->validate($rules);

        foreach (($validated['variants'] ?? []) as $index => $variant) {
            if (($variant['sale_price'] ?? null) !== null && (float) $variant['sale_price'] >= (float) $variant['price']) {
                throw ValidationException::withMessages([
                    "variants.$index.sale_price" => 'Sale price must be lower than the regular price.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $product, $updating, $variantModel): void {
            $slug = trim((string) ($validated['slug'] ?? '')) ?: Str::slug($validated['name']);
            if ($slug === '') {
                $slug = 'product-' . Str::lower(Str::random(8));
            }

            $product->fill([
                'name' => $validated['name'],
                'slug' => $slug,
                'category_id' => $validated['category_id'] ?? null,
                'brand_id' => $validated['brand_id'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'description' => $validated['description'] ?? null,
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'is_active' => ($validated['status'] ?? 'draft') === 'active',
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'is_hero' => (bool) ($validated['is_hero'] ?? false),
            ]);
            $product->save();

            $keepIds = [];
            $seenSkus = [];
            $seenBarcodes = [];

            foreach (array_values($validated['variants']) as $index => $input) {
                $variantId = $input['id'] ?? null;
                $sku = trim((string) ($input['sku'] ?? '')) ?: null;
                $barcode = trim((string) ($input['barcode'] ?? '')) ?: null;

                if ($sku !== null && in_array($sku, $seenSkus, true)) {
                    throw ValidationException::withMessages([
                        "variants.$index.sku" => 'Each variant must have a different SKU.',
                    ]);
                }
                if ($barcode !== null && in_array($barcode, $seenBarcodes, true)) {
                    throw ValidationException::withMessages([
                        "variants.$index.barcode" => 'Each variant must have a different barcode.',
                    ]);
                }
                if ($sku !== null) {
                    $exists = $variantModel::query()->where('sku', $sku)
                        ->when($variantId, fn ($q) => $q->where('id', '<>', $variantId))->exists();
                    if ($exists) {
                        throw ValidationException::withMessages([
                            "variants.$index.sku" => 'This SKU is already used by another product variant.',
                        ]);
                    }
                    $seenSkus[] = $sku;
                }
                if ($barcode !== null) {
                    $exists = $variantModel::query()->where('barcode', $barcode)
                        ->when($variantId, fn ($q) => $q->where('id', '<>', $variantId))->exists();
                    if ($exists) {
                        throw ValidationException::withMessages([
                            "variants.$index.barcode" => 'This barcode is already used by another product variant.',
                        ]);
                    }
                    $seenBarcodes[] = $barcode;
                }

                if ($variantId) {
                    $variant = $product->variants()->whereKey($variantId)->firstOrFail();
                } else {
                    $variant = $product->variants()->make();
                }

                $variant->fill([
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'size' => $input['size'] ?? null,
                    'color' => $input['color'] ?? null,
                    'color_code' => $input['color_code'] ?? null,
                    'price' => $input['price'],
                    'sale_price' => ($input['sale_price'] ?? '') !== '' ? $input['sale_price'] : null,
                    'stock' => (int) $input['stock'],
                    'low_stock_threshold' => (int) $input['low_stock_threshold'],
                    'is_active' => (bool) $input['is_active'],
                    'sort_order' => $index,
                ]);
                $variant->save();
                $keepIds[] = $variant->getKey();
            }

            if ($updating) {
                $product->variants()->whereNotIn('id', $keepIds)->get()->each(function ($variant): void {
                    // Soft delete keeps historical order-line references intact.
                    $variant->delete();
                });
            }
        });

        if ($request->hasFile('images')) {
            $mediaDisk = config('commerce-admin.media_disk', 'public');
            foreach ($request->file('images', []) as $image) {
                $path = $image->store(
                    trim(config('commerce-admin.media_directory', 'products'), '/') . '/' . $product->getKey(),
                    $mediaDisk
                );

                $product->media()->create([
                    'collection' => 'gallery',
                    'disk' => $mediaDisk,
                    'path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'alt_text' => $product->name,
                    'sort_order' => $product->galleryMedia()->count(),
                    'uploaded_by' => Auth::guard(config('commerce-admin.guard', 'web'))->id(),
                ]);
            }
        }

        return redirect()->route('commerce-admin.products.show', $product->getKey())
            ->with('status', $updating ? 'Product changes saved.' : 'Product created.');
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
        $asset = $model->media()->whereKey($media)->firstOrFail();

        if ($asset->path && ! preg_match('/^https?:\/\//i', $asset->path)) {
            Storage::disk($asset->disk ?: config('commerce-admin.media_disk', 'public'))->delete($asset->path);
        }

        $asset->delete();

        return redirect()->route('commerce-admin.products.edit', $model->getKey())
            ->with('status', 'Image removed.');
    }
}
