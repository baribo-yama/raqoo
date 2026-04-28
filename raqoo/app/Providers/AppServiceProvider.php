<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


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
        // 本番 or FORCE_HTTPS=true のときは無条件に HTTPS を強制
        if ($this->app->environment('production') || config('app.force_https')) {
            URL::forceScheme('https');
            return;
        }

        // 開発環境でngrokを使うときだけHTTPSを強制する
        if (config('app.env') === 'local' && str_contains(request()->getHost(), '.ngrok')) {
            URL::forceScheme('https');
        }
    }
}
