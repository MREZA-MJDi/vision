<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SaveBrandRequest;
use App\Services\Catalog\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BrandController
{
    public function __construct(
        private readonly BrandService $brands,
    ) {}

    public function index(Request $request): View
    {
        $model = $this->brands->model();

        $search = mb_substr(
            trim($request->string('q')->toString()),
            0,
            120
        );

        $status = $request->string('status', 'all')->toString();

        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $items = $model::query()
            ->with('logoMedia')
            ->withCount('products')
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                )
            )
            ->when(
                $status !== 'all',
                fn ($query) => $query->where(
                    'is_active',
                    $status === 'active'
                )
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate((int) config(
                'commerce-admin.items_per_page',
                10
            ))
            ->withQueryString();

        $stats = [
            'all' => $model::query()->count(),
            'active' => $model::query()
                ->where('is_active', true)->count(),
            'used' => $model::query()->whereHas('products')->count(),
        ];

        return view('commerce-admin::brands.index', compact(
            'items',
            'search',
            'status',
            'stats',
        ));
    }

    public function create(): View
    {
        $model = $this->brands->model();

        return view('commerce-admin::brands.create', [
            'record' => new $model(),
        ]);
    }

    public function store(
        SaveBrandRequest $request
    ): RedirectResponse {
        $model = $this->brands->model();

        $this->brands->save(
            new $model(),
            $request->validated(),
            $request->file('logo_image')
        );

        return redirect()
            ->route('commerce-admin.brands.index')
            ->with('status', 'برند با موفقیت ایجاد شد.');
    }

    public function edit(int $brand): View
    {
        $model = $this->brands->model();

        return view('commerce-admin::brands.edit', [
            'record' => $model::query()
                ->with('logoMedia')
                ->findOrFail($brand),
        ]);
    }

    public function update(
        SaveBrandRequest $request,
        int $brand
    ): RedirectResponse {
        $model = $this->brands->model();

        $this->brands->save(
            $model::query()->findOrFail($brand),
            $request->validated(),
            $request->file('logo_image')
        );

        return redirect()
            ->route('commerce-admin.brands.index')
            ->with('status', 'تغییرات برند ذخیره شد.');
    }

    public function destroy(int $brand): RedirectResponse
    {
        $model = $this->brands->model();

        $deleted = $this->brands->destroy(
            $model::query()->findOrFail($brand)
        );

        return redirect()
            ->route('commerce-admin.brands.index')
            ->with(
                'status',
                $deleted
                    ? 'برند حذف شد.'
                    : 'برند دارای محصول است و غیرفعال شد.'
            );
    }
}
