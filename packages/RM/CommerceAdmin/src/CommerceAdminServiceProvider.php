<?php

namespace Rm\CommerceAdmin;

use Illuminate\Support\ServiceProvider;

class CommerceAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $packagePath = dirname(__DIR__);

        // The kit owns presentation only; application routes and business logic live in app/.
        $this->loadViewsFrom($packagePath . '/resources/views', 'commerce-admin');

        $this->publishes([
            $packagePath . '/resources/css/admin-kit.css' => public_path('vendor/commerce-admin/admin-kit.css'),
            $packagePath . '/resources/js/admin-kit.js' => public_path('vendor/commerce-admin/admin-kit.js'),
        ], 'commerce-admin-assets');
    }
}
