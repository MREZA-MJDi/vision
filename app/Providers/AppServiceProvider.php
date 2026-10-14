<?php

namespace App\Providers;

use App\Support\JalaliDate;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('money', static fn (string $expression): string =>
            "<?php echo e(\\App\\Support\\Money::format({$expression})); ?>"
        );
        Blade::directive('jalali', static fn (string $expression): string =>
            "<?php echo e(\\App\\Support\\JalaliDate::format({$expression})); ?>"
        );

        Gate::define('access-commerce-admin', static function (Model $user): bool {
            $adminFlagColumn = (string) config('commerce-admin.admin_flag_column', 'is_admin');

            return $adminFlagColumn !== ''
                && (bool) $user->getAttribute($adminFlagColumn);
        });
    }
}
