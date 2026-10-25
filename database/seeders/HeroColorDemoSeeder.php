<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Opt-in staging/demo helper: pin eight already-seeded, photo-backed colorways
 * into the homepage hero playlist. It does not create fake photos, variants,
 * inventory, or orders; product and variant media remain source-of-truth data.
 *
 * Run only when you want to preview the multi-color hero in a demo environment:
 * php artisan db:seed --class=HeroColorDemoSeeder
 */
class HeroColorDemoSeeder extends Seeder
{
    /**
     * One product per visible color family so the home hero demonstrates
     * black, cream, chestnut, orange, pink, blue, charcoal and light blue.
     */
    private const HERO_SLUGS = [
        'vision-black-puffer',
        'vision-cream-turtleneck',
        'vision-chestnut-trousers',
        'vision-solar-puffer',
        'vision-pink-cardigan',
        'vision-blue-jeans',
        'vision-charcoal-hoodie',
        'vision-light-blue-shirt',
    ];

    public function run(): void
    {
        $products = Product::query()
            ->active()
            ->whereIn('slug', self::HERO_SLUGS)
            ->whereHas('activeVariants.primaryGalleryMedia')
            ->with(['activeVariants.primaryGalleryMedia'])
            ->get()
            ->keyBy('slug');

        foreach (self::HERO_SLUGS as $slug) {
            $product = $products->get($slug);

            if (! $product) {
                $this->command?->warn(
                    "Hero color demo skipped [{$slug}]: run FittingRoomAssetSeeder first and confirm the product has an active variant."
                );

                continue;
            }

            if (! $product->is_hero) {
                $product->forceFill(['is_hero' => true])->save();
            }

            $variant = $product->activeVariants
                ->first(fn ($item) => (int) $item->stock > 0)
                ?? $product->activeVariants->first();

            $this->command?->line(sprintf(
                'Hero color: %-30s %s',
                $product->name,
                $variant?->color ?: 'بدون رنگ نام‌گذاری‌شده'
            ));
        }

        $this->command?->info(
            'Hero color demo prepared. The home hero now uses real seeded product photos and each variant’s saved color_code.'
        );
    }
}
