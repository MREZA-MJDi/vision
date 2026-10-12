<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->with(['brand:id,name', 'primaryActiveVariant:id,product_id,price,sale_price,stock', 'primaryGalleryMedia'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $newProducts = Product::query()
            ->active()
            ->with(['brand:id,name', 'primaryActiveVariant:id,product_id,price,sale_price,stock', 'primaryGalleryMedia'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->active()
            ->with('coverMedia')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(6)
            ->get();

        return view('store.home', compact('featuredProducts', 'newProducts', 'categories'));
    }

    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['newest', 'price_asc', 'price_desc'], true)
            ? $request->query('sort')
            : 'newest';

        $term = trim((string) $request->query('q', ''));
        $safeTerm = addcslashes($term, "%_\\\\");
        $products = Product::query()
            ->active()
            ->with(['brand:id,name', 'category:id,name', 'primaryActiveVariant:id,product_id,price,sale_price,stock', 'primaryGalleryMedia'])
            ->when($term !== '', function ($query) use ($safeTerm) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', '%' . $safeTerm . '%')
                    ->orWhere('short_description', 'like', '%' . $safeTerm . '%'));
            })
            ->when($request->filled('category'), fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', $request->query('category'))->where('is_active', true)))
            ->when($request->filled('brand'), fn ($query) => $query->whereHas('brand', fn ($brand) => $brand->where('slug', $request->query('brand'))->where('is_active', true)));

        match ($sort) {
            'price_asc' => $products->orderByEffectivePrice('asc'),
            'price_desc' => $products->orderByEffectivePrice('desc'),
            default => $products->latest('created_at'),
        };

        $products = $products->paginate(12)->withQueryString();
        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);
        $brands = Brand::query()->active()->orderBy('name')->get(['id', 'name', 'slug']);

        return view('store.products.index', compact('products', 'categories', 'brands', 'sort'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'brand:id,name,slug',
            'category:id,name,slug',
            'activeVariants:id,product_id,sku,size,color,color_code,price,sale_price,stock',
            'galleryMedia',
        ]);

        $relatedProducts = Product::query()
            ->active()
            ->where('id', '!=', $product->getKey())
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->with(['brand:id,name', 'primaryActiveVariant:id,product_id,price,sale_price,stock', 'primaryGalleryMedia'])
            ->latest('created_at')
            ->limit(4)
            ->get();

        return view('store.products.show', compact('product', 'relatedProducts'));
    }
}
