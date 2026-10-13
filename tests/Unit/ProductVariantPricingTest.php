<?php

namespace Tests\Unit;

use App\Models\ProductVariant;
use PHPUnit\Framework\TestCase;

class ProductVariantPricingTest extends TestCase
{
    public function test_valid_sale_price_is_used_as_effective_price(): void
    {
        $variant = new ProductVariant([
            'price' => '125.00',
            'sale_price' => '99.95',
        ]);

        $this->assertSame(99.95, $variant->effective_price);
        $this->assertTrue($variant->is_on_sale);
    }

    public function test_sale_price_above_regular_price_falls_back_to_regular_price(): void
    {
        $variant = new ProductVariant([
            'price' => '125.00',
            'sale_price' => '149.00',
        ]);

        $this->assertSame(125.0, $variant->effective_price);
        $this->assertFalse($variant->is_on_sale);
    }

    public function test_negative_legacy_sale_price_falls_back_to_regular_price(): void
    {
        $variant = new ProductVariant([
            'price' => '125.00',
            'sale_price' => '-5.00',
        ]);

        $this->assertSame(125.0, $variant->effective_price);
        $this->assertFalse($variant->is_on_sale);
    }

    public function test_missing_sale_price_uses_regular_price(): void
    {
        $variant = new ProductVariant([
            'price' => '125.00',
            'sale_price' => null,
        ]);

        $this->assertSame(125.0, $variant->effective_price);
        $this->assertFalse($variant->is_on_sale);
    }
}
