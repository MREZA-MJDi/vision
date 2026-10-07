<?php

namespace App\Http\Controllers;

use App\Services\HeroService;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __invoke(HeroService $heroService): View
    {
        return view('shop.home', [
            'slides' => $heroService->slides(),
        ]);
    }
}
