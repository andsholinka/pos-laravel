<?php

namespace App\Providers;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        Carbon::setLocale(config('app.locale'));

        // Any lazy load throws outside production, so N+1 queries surface during development.
        Model::preventLazyLoading(! $this->app->isProduction());

        View::composer(
            ['layouts.*', 'auth.*', 'components.app-sidebar', 'sales.receipt', 'reports.pdf'],
            fn ($view) => $view->with('store', StoreSetting::current()),
        );
    }
}
