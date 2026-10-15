<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Rm\VirtualTryOn\Models\TryOnMannequin;

class VirtualTryOnMannequinSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'code' => 'women-front',
                'label' => 'مانکن زنانه — نمای روبه‌رو',
                'audience' => 'women',
                'pose_key' => 'front-neutral',
                'canvas_width' => 1024,
                'canvas_height' => 1536,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'men-front',
                'label' => 'مانکن مردانه — نمای روبه‌رو',
                'audience' => 'men',
                'pose_key' => 'front-neutral',
                'canvas_width' => 1024,
                'canvas_height' => 1536,
                'is_active' => true,
                'sort_order' => 20,
            ],
        ] as $mannequin) {
            TryOnMannequin::query()->updateOrCreate(['code' => $mannequin['code']], $mannequin);
        }
    }
}
