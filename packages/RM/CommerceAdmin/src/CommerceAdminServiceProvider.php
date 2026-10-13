<?php

namespace Rm\CommerceAdmin;

use Illuminate\Support\ServiceProvider;
use Rm\CommerceAdmin\Console\MakeAdminCommand;

class CommerceAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/commerce-admin.php', 'commerce-admin');
    }

    public function boot(): void
    {
        $packagePath = dirname(__DIR__);

        $this->loadRoutesFrom($packagePath . '/routes/web.php');
        $this->loadViewsFrom($packagePath . '/resources/views', 'commerce-admin');
        $this->loadMigrationsFrom($packagePath . '/database/migrations');

        $this->publishes([
            $packagePath . '/config/commerce-admin.php' => config_path('commerce-admin.php'),
        ], 'commerce-admin-config');

        $this->publishes([
            $packagePath . '/resources/css/admin-kit.css' => public_path('vendor/commerce-admin/admin-kit.css'),
            $packagePath . '/resources/js/admin-kit.js' => public_path('vendor/commerce-admin/admin-kit.js'),
        ], 'commerce-admin-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([MakeAdminCommand::class]);
        }
    }
}
