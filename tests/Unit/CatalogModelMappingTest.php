<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Services\Catalog\BrandService;
use App\Services\Catalog\CategoryService;
use Tests\TestCase;

final class CatalogModelMappingTest extends TestCase
{
    public function test_catalog_services_resolve_their_own_models(): void
    {
        $this->assertSame(Category::class, app(CategoryService::class)->model());
        $this->assertSame(Brand::class, app(BrandService::class)->model());
    }
}
