<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandDemoSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::withTrashed()->firstOrNew(['slug' => 'vision-demo']);
        if ($brand->exists) {
            $this->command?->info('Demo brand already exists; preserving its current state and edits.');

            return;
        }

        $brand->fill([
            'name' => 'VISION Studio',
            'slug' => 'vision-demo',
            'description' => 'برند نمونه برای تست مدیریت کاتالوگ و صفحه محصول.',
            'meta_title' => 'VISION Studio',
            'meta_description' => 'نمونه برند فروشگاه VISION.',
            'is_active' => true,
        ])->save();

        $this->command?->info('Demo brand is ready.');
    }
}
