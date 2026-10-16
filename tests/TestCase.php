<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        $knownRiskyTests = [
            'test_category_cover_image_is_saved_replaced_and_removed_from_the_public_storefront',
            'test_admin_hero_selection_adds_to_the_homepage_playlist',
            'test_the_public_homepage_renders_without_authentication',
            'test_home_page_renders_public_storefront_without_authentication',
            'test_product_detail_shows_variant_and_returns_404_for_inactive_product',
        ];

        $testName = $this->toString();
        foreach ($knownRiskyTests as $method) {
            if (str_contains($testName, $method)) {
                $buffers = array_map(
                    static fn (array $buffer): array => array_intersect_key(
                        $buffer,
                        array_flip(['name', 'type', 'flags', 'level', 'chunk_size', 'buffer_size', 'buffer_used'])
                    ),
                    ob_get_status(true)
                );

                fwrite(STDERR, sprintf(
                    "\n[OB-DEBUG] test=%s level=%d buffers=%s\n",
                    $testName,
                    ob_get_level(),
                    json_encode($buffers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ));
                break;
            }
        }

        parent::tearDown();
    }
}
