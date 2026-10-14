<?php

namespace Rm\\CommerceAdmin;

use Illuminate\\Support\\Facades\\Blade;
use Illuminate\\Support\\ServiceProvider;

class CommerceAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/commerce-admin.php', 'commerce-admin');
    }

    public function boot(): void
    {
        $packagePath = dirname(__DIR__);

        // UI and defaults are package-owned; routes, authorization and business rules
        // remain in the consuming Laravel application.
        $this->loadViewsFrom($packagePath . '/resources/views', 'commerce-admin');

        Blade::directive('commerceMoney', static fn (string $expression): string =>
            "<?php echo e(app(config('commerce-admin.formatters.money', \\Rm\\CommerceAdmin\\Support\\MoneyFormatter::class))->format({$expression})); ?>"
        );
        Blade::directive('commerceDate', static fn (string $expression): string =>
            "<?php echo e(app(config('commerce-admin.formatters.date', \\Rm\\CommerceAdmin\\Support\\JalaliDateFormatter::class))->format({$expression})); ?>"
        );

        $this->publishes([
            $packagePath . '/config/commerce-admin.php' => config_path('commerce-admin.php'),
        ], 'commerce-admin-config');

        $this->publishes([
            $packagePath . '/resources/css/admin-kit.css' => public_path('vendor/commerce-admin/admin-kit.css'),
            $packagePath . '/resources/js/admin-kit.js' => public_path('vendor/commerce-admin/admin-kit.js'),
        ], 'commerce-admin-assets');
    }
}
