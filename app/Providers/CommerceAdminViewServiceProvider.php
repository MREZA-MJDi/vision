<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CommerceAdminViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $packagePath = base_path('packages/RM/CommerceAdmin');

        $this->loadViewsFrom($packagePath . '/resources/views', 'commerce-admin');

        $this->publishes([
            $packagePath . '/resources/css/admin-kit.css' => public_path('vendor/commerce-admin/admin-kit.css'),
            $packagePath . '/resources/js/admin-kit.js' => public_path('vendor/commerce-admin/admin-kit.js'),
        ], 'commerce-admin-assets');
    }
}
