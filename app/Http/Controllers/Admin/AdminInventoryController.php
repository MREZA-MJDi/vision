<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInventoryMovementRequest;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AdminInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $variants = ProductVariant::query()
            ->with(['product.category'])
            ->whereHas('product')
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $search = $request->string('q')->toString();

                    $query->whereHas(
                        'product',
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'slug',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->orderBy('stock')
            ->orderBy('id')
            ->paginate(18)
            ->withQueryString();

        $movements = InventoryMovement::query()
            ->with([
                'productVariant.product',
                'createdBy',
            ])
            ->latest()
            ->limit(20)
            ->get();

        $selectedVariant = old('product_variant_id')
            ? ProductVariant::query()->with('product')->find(old('product_variant_id'))
            : null;

        return view(
            'admin.inventory.index',
            compact(
                'variants',
                'movements',
                'selectedVariant'
            )
        );
    }

    public function store(
        StoreInventoryMovementRequest $request
    ): RedirectResponse {
        try {
            $data = $request->validated();

            DB::transaction(function () use ($data): void {
                $variant = ProductVariant::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['product_variant_id']
                    );

                $quantity = (int) $data['quantity'];

                $newStock = (int) $variant->stock + $quantity;

                abort_if(
                    $newStock < 0,
                    422,
                    'موجودی نمی‌تواند منفی شود.'
                );

                $variant->update([
                    'stock' => $newStock,
                ]);

                $variant->inventoryMovements()->create([
                    'type' => $data['type'],
                    'quantity' => $quantity,
                    'stock_after' => $newStock,
                    'note' => $data['note'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            });

            return back()->with(
                'success',
                'گردش موجودی با موفقیت ثبت شد.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'ثبت گردش موجودی انجام نشد.'
                );
        }
    }
}
