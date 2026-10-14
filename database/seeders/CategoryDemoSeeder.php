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
            if ($category->exists && $category->trashed()) {
                $category->restore();
            }

            $category->fill($attributes + ['is_active' => true]);
            $category->save();
        }

        $this->command?->info('Demo categories are ready. They remain editable in Commerce Admin.');
    }
}
