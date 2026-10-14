<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        Gate::define('access-commerce-admin', static function (Model $user): bool {
            $adminFlagColumn = (string) config('commerce-admin.admin_flag_column', 'is_admin');

            return $adminFlagColumn !== ''
                && (bool) $user->getAttribute($adminFlagColumn);
        });
    }
}
