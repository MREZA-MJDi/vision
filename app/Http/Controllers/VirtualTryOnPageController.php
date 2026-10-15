<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class VirtualTryOnPageController extends Controller
{
    public function __invoke(): View
    {
        return view('store.virtual-try-on');
    }
}
