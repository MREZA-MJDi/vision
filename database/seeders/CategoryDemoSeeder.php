<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'demo-apparel',
                'name' => 'پوشاک',
                'description' => 'نمونه دسته‌بندی پوشاک برای بررسی فروشگاه و تنوع‌های رنگی.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'demo-accessories',
                'name' => 'اکسسوری',
                'description' => 'نمونه دسته‌بندی کیف و اکسسوری.',
                'sort_order' => 20,
            ],
        ];

        foreach ($categories as $attributes) {
            $category = Category::withTrashed()->firstOrNew(['slug' => $attributes['slug']]);

            // Demo seeders provide initial data only. Never overwrite admin edits
            // or silently restore a record that an administrator archived.
            if ($category->exists) {
                continue;
            }

            $category->fill($attributes + ['is_active' => true])->save();
        }

        $this->command?->info('Demo categories are ready. They remain editable in Commerce Admin.');
    }
}
