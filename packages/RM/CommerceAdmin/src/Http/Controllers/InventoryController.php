<?php

namespace Rm\CommerceAdmin\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController
{
    private function model(string $key, string $fallback): string
    {
        return (string) config('commerce-admin.models.' . $key, $fallback);
    }

    public function index(Request $request): View
    {
        $variantModel = $this->model('variant', ProductVariant::class);
        $movementModel = $this->model('inventory_movement', InventoryMovement::class);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $query = $variantModel::query()->with(['product', 'primaryGalleryMedia']);

        if ($search !== '') {
            $query->where(fn ($variants) => $variants
                ->where('sku', 'like', '%' . $search . '%')
                ->orWhere('barcode', 'like', '%' . $search . '%')
                ->orWhereHas('product', fn ($product) => $product->where('name', 'like', '%' . $search . '%')));
        }

        if ($status === 'low_stock') {
            $query->where('is_active', true)->whereColumn('stock', '<=', 'low_stock_threshold');
        } elseif ($status === 'out_of_stock') {
            $query->where('is_active', true)->where('stock', '<=', 0);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } else {
            $status = 'all';
        }

        $variants = $query->orderBy('stock')->orderByDesc('updated_at')
            ->paginate((int) config('commerce-admin.items_per_page', 10))
            ->withQueryString();

        $movementQuery = $movementModel::query()->with(['productVariant.product', 'createdBy']);
        $movements = $movementQuery->orderByDesc('created_at')
            ->paginate(8, ['*'], 'movements_page')
            ->withQueryString();

        $stats = [
            'variants' => $variantModel::query()->where('is_active', true)->count(),
            'low_stock' => $variantModel::query()->where('is_active', true)
                ->whereColumn('stock', '<=', 'low_stock_threshold')->count(),
            'out_of_stock' => $variantModel::query()->where('is_active', true)->where('stock', '<=', 0)->count(),
            'movements' => $movementModel::query()->count(),
        ];

        return view('commerce-admin::inventory.index', compact(
            'variants', 'movements', 'stats', 'search', 'status'
        ));
    }

    public function adjust(Request $request, int $variant): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $delta = (int) $validated['adjustment'];
        if ($delta === 0) {
            throw ValidationException::withMessages([
                'adjustment' => 'برای اصلاح موجودی، عددی غیر از صفر وارد کنید.',
            ]);
        }

        $variantModel = $this->model('variant', ProductVariant::class);
        $movementModel = $this->model('inventory_movement', InventoryMovement::class);
        $guard = (string) config('commerce-admin.guard', 'web');
        $actorId = Auth::guard($guard)->id();

        DB::transaction(function () use ($variantModel, $movementModel, $variant, $delta, $validated, $actorId): void {
            $record = $variantModel::query()->lockForUpdate()->findOrFail($variant);
            $nextStock = (int) $record->stock + $delta;

            if ($nextStock < 0) {
                throw ValidationException::withMessages([
                    'adjustment' => 'موجودی نمی‌تواند منفی شود.',
                ]);
            }

            $record->forceFill(['stock' => $nextStock])->save();

            $movementModel::query()->create([
                'product_variant_id' => $record->getKey(),
                'type' => 'adjustment',
                'quantity' => $delta,
                'stock_after' => $nextStock,
                'reference_type' => null,
                'reference_id' => null,
                'note' => trim($validated['note']),
                'created_by' => $actorId,
            ]);
        }, 3);

        return redirect()->route('commerce-admin.inventory.index', array_filter([
            'q' => $request->query('q'),
            'status' => $request->query('status'),
            'page' => $request->query('page'),
        ]))->with('status', 'موجودی اصلاح شد و تغییر در تاریخچه ثبت شد.');
    }
}
