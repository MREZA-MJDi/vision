<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\WholesalePack;
use App\Support\NumericInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminWholesalePackController extends Controller
{
    public function index(): View
    {
        $packs = WholesalePack::query()
            ->select(['id','name','slug','pack_quantity','pack_price','sort_order','is_active'])
            ->with([
                'items' => fn ($query) => $query->select([
                    'id','wholesale_pack_id','product_variant_id','quantity',
                ]),
                'items.variant' => fn ($query) => $query->select([
                    'id','product_id','sku','size','color','sort_order',
                ]),
                'items.variant.product:id,name,brand_id',
                'items.variant.product.brand:id,name',
            ])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.wholesale-packs.index', compact('packs'));
    }

    public function create(): View
    {
        return view('admin.wholesale-packs.form', [
            'pack' => new WholesalePack(),
            'variants' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $image = $data['image'] ?? null;
        unset($data['image'], $data['remove_image']);
        if ($image) {
            $data['image_path'] = $image->store('wholesale-packs', 'public');
        }

        $pack = DB::transaction(function () use ($data): WholesalePack {
            $items = $data['items'];
            unset($data['items']);

            $pack = WholesalePack::create($data);
            $pack->items()->createMany($items);
            $pack->update(['pack_quantity' => $pack->items()->sum('quantity')]);

            return $pack;
        });

        return redirect()
            ->route('admin.wholesale-packs.edit', $pack)
            ->with('success', 'پک عمده با موفقیت ساخته شد.');
    }

    public function edit(WholesalePack $wholesalePack): View
    {
        $wholesalePack->load('items.variant.product.brand');

        return view('admin.wholesale-packs.form', [
            'pack' => $wholesalePack,
            'variants' => $wholesalePack->items->pluck('variant')->filter()->values(),
        ]);
    }

    public function update(Request $request, WholesalePack $wholesalePack): RedirectResponse
    {
        $data = $this->validated($request);
        $image = $data['image'] ?? null;
        $removeImage = $request->boolean('remove_image');
        unset($data['image'], $data['remove_image']);
        if ($image) $data['image_path'] = $image->store('wholesale-packs', 'public');
        elseif ($removeImage) $data['image_path'] = null;

        DB::transaction(function () use ($data, $wholesalePack): void {
            $items = $data['items'];
            unset($data['items']);
            $wholesalePack->update($data);
            $wholesalePack->items()->delete();
            $wholesalePack->items()->createMany($items);
            $wholesalePack->update(['pack_quantity' => $wholesalePack->items()->sum('quantity')]);
        });

        return back()->with('success', 'پک عمده به‌روزرسانی شد.');
    }

    public function destroy(WholesalePack $wholesalePack): RedirectResponse
    {
        $wholesalePack->delete();
        return back()->with('success', 'پک عمده حذف شد.');
    }

    private function validated(Request $request): array
    {
        $routePack = $request->route('wholesale_pack') ?? $request->route('wholesalePack');
        $ignorePackId = $routePack instanceof WholesalePack ? $routePack->getKey() : $routePack;

        $request->merge([
            'pack_price' => NumericInput::normalize($request->input('pack_price')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('wholesale_packs', 'slug')->ignore($ignorePackId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'pack_price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $variantIds = collect($data['items'])->pluck('variant_id');

        $validCount = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->count();

        abort_unless($validCount === $variantIds->count(), 422, 'یکی از Variantهای انتخاب‌شده فعال نیست.');

        $missingWholesalePrice = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->whereNull('wholesale_price')
            ->exists();

        abort_unless(! $missingWholesalePrice, 422, 'تمام Variantهای داخل پک باید قیمت عمده داشته باشند.');

        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['name']) . '-' . Str::lower(Str::random(5));

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['items'] = collect($data['items'])
            ->map(fn (array $item) => [
                'product_variant_id' => (int) $item['variant_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->values()
            ->all();

        return $data;
    }
}
