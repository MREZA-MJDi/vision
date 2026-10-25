<?php

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rm\VirtualTryOn\Services\CompositionBuilder;

class VirtualTryOnCompositionBuilderTest extends TestCase
{
    public function test_composition_uses_stable_layer_order_without_losing_other_slots(): void
    {
        $result = (new CompositionBuilder())->build('women', [
            ['slot' => 'top', 'audience' => 'women', 'variant_id' => 12, 'layer_url' => '/media/top.webp'],
            ['slot' => 'shoes', 'audience' => 'women', 'variant_id' => 42, 'layer_url' => '/media/shoes.webp'],
            ['slot' => 'outerwear', 'audience' => 'women', 'variant_id' => 19, 'layer_url' => '/media/coat.webp'],
        ]);

        self::assertSame(['shoes', 'top', 'outerwear'], array_column($result, 'slot'));
        self::assertSame([42, 12, 19], array_column($result, 'variant_id'));
        self::assertSame([10, 30, 40], array_column($result, 'z_index'));
    }

    public function test_composition_rejects_audience_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CompositionBuilder())->build('women', [
            ['slot' => 'top', 'audience' => 'men', 'variant_id' => 1, 'layer_url' => '/media/item.webp'],
        ]);
    }

    public function test_composition_rejects_duplicate_slots_and_missing_layers(): void
    {
        try {
            (new CompositionBuilder())->build('women', [
                ['slot' => 'top', 'audience' => 'women', 'variant_id' => 1, 'layer_url' => '/media/one.webp'],
                ['slot' => 'top', 'audience' => 'women', 'variant_id' => 2, 'layer_url' => '/media/two.webp'],
            ]);
            self::fail('Duplicate slots must be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('one selected variant', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        (new CompositionBuilder())->build('women', [
            ['slot' => 'top', 'audience' => 'women', 'variant_id' => 1, 'layer_url' => null],
        ]);
    }
}
