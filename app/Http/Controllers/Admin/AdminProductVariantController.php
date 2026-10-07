<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminProductVariantController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $variants = ProductVariant::query()
            ->with(['product:id,name,slug,brand_id', 'product.brand:id,name'])
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('sku', 'like', "%{$term}%")
                        ->orWhere('color', 'like', "%{$term}%")
                        ->orWhere('size', 'like', "%{$term}%")
                        ->orWhereHas('product', function ($product) use ($term): void {
                            $product->where('name', 'like', "%{$term}%")
                                ->orWhere('slug', 'like', "%{$term}%")
                                ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$term}%"));
                        });
                });
            })
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->simplePaginate(20)
            ->withQueryString();

        return response()->json([
            'data' => $variants->getCollection()->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->product?->name ?? 'محصول',
                'brand' => $variant->product?->brand?->name ?? 'بدون برند',
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'display_name' => $variant->display_name,
                'stock' => (int) $variant->stock,
            ])->values(),
            'next_page_url' => $variants->nextPageUrl(),
            'current_page' => $variants->currentPage(),
        ]);
    }

    public function index(Product $product): View
    {
        $product->load([
            'category',
            'brand',
        ]);

        $variants = $product->variants()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.variants.index', [
            'product' => $product,
            'variants' => $variants,
        ]);
    }

    public function create(Product $product): View
    {
        return view('admin.variants.create', [
            'product' => $product,
        ]);
    }

    public function store(
        StoreProductVariantRequest $request,
        Product $product
    ): RedirectResponse {
        $data = $request->validated();

        $data['product_id'] = $product->getKey();

        $data['sku'] = filled($data['sku'] ?? null)
            ? $data['sku']
            : $this->generateUniqueSku();

        $data['low_stock_threshold'] = (int) (
            $data['low_stock_threshold'] ?? 5
        );

        $data['sort_order'] = (int) (
            $data['sort_order'] ?? 0
        );

        $data['is_active'] = $request->boolean(
            'is_active',
            true
        );

        ProductVariant::create($data);

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with(
                'success',
                'واریانت محصول با موفقیت ایجاد شد.'
            );
    }

    public function edit(
        Product $product,
        ProductVariant $variant
    ): View {
        $this->ensureVariantBelongsToProduct(
            $product,
            $variant
        );

        $product->load([
            'category',
            'brand',
        ]);

        return view('admin.variants.edit', [
            'product' => $product,
            'variant' => $variant,
        ]);
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant
    ): RedirectResponse {
        $this->ensureVariantBelongsToProduct(
            $product,
            $variant
        );

        $data = $request->validated();

        /*
         * موجودی مستقیم از این فرم تغییر نمی‌کند.
         * تغییر stock باید از مسیر InventoryMovement / InventoryService
         * انجام شود تا سابقه گردش موجودی حفظ شود.
         */
        unset($data['stock']);

        /*
         * اگر SKU خالی باشد، خودکار ساخته می‌شود.
         */
        $data['sku'] = filled($data['sku'] ?? null)
            ? $data['sku']
            : $this->generateUniqueSku(
                $variant->getKey()
            );

        $data['low_stock_threshold'] = (int) (
            $data['low_stock_threshold'] ?? 5
        );

        $data['sort_order'] = (int) (
            $data['sort_order'] ?? 0
        );

        $data['is_active'] = $request->boolean(
            'is_active'
        );

        /*
         * محصول از Route مشخص است و نباید از فرم تغییر کند.
         */
        unset($data['product_id']);

        $variant->update($data);

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with(
                'success',
                'واریانت محصول با موفقیت به‌روزرسانی شد.'
            );
    }

    public function destroy(
        Product $product,
        ProductVariant $variant
    ): RedirectResponse {
        $this->ensureVariantBelongsToProduct(
            $product,
            $variant
        );

        /*
         * اگر در سفارش استفاده شده باشد،
         * به‌جای حذف فیزیکی غیرفعال می‌شود.
         */
        if ($variant->orderItems()->exists()) {

            $variant->update([
                'is_active' => false,
            ]);

            return redirect()
                ->route('admin.products.variants.index', $product)
                ->with(
                    'success',
                    'این واریانت قبلاً در سفارش استفاده شده بود و به‌جای حذف، غیرفعال شد.'
                );
        }

        /*
         * اگر سابقه گردش انبار داشته باشد،
         * حذف فیزیکی سابقه انبار را مخدوش می‌کند.
         */
        if ($variant->inventoryMovements()->exists()) {

            $variant->update([
                'is_active' => false,
            ]);

            return redirect()
                ->route('admin.products.variants.index', $product)
                ->with(
                    'success',
                    'این واریانت سابقه گردش انبار داشت و به‌جای حذف، غیرفعال شد.'
                );
        }

        $variant->delete();

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with(
                'success',
                'واریانت محصول حذف شد.'
            );
    }

    private function generateUniqueSku(
        ?int $ignoreId = null
    ): string {
        do {
            $sku = 'JAN-' . strtoupper(
                    Str::random(8)
                );

            $exists = ProductVariant::query()
                ->where('sku', $sku)
                ->when(
                    $ignoreId !== null,
                    fn ($query) => $query->whereKeyNot($ignoreId)
                )
                ->exists();

        } while ($exists);

        return $sku;
    }

    private function ensureVariantBelongsToProduct(
        Product $product,
        ProductVariant $variant
    ): void {
        abort_unless(
            (int) $variant->product_id === (int) $product->id,
            404
        );
    }
}
