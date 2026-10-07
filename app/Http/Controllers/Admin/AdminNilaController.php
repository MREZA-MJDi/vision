<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationMapping;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNilaController extends Controller
{
    public function index(Request $request): View
    {
        $base = IntegrationMapping::query()->where('integration', 'nila');

        $products = (clone $base)->where('entity_type', Product::class)->count();
        $variants = (clone $base)->where('entity_type', ProductVariant::class)->count();

        $mappings = (clone $base)
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $latest = $mappings->first();

        return view('admin.nila.index', [
            'products' => $products,
            'variants' => $variants,
            'mappings' => $mappings,
            'latestSyncAt' => $latest?->updated_at,
        ]);
    }
}
