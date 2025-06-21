<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Pulse\Facades\Pulse;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Elasticsearch client to the service container
        $this->app->singleton('elasticsearch', function () {
            return ClientBuilder::create()
                ->setHosts(['http://localhost:9200'])
                ->build();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
       
    
        $this->configureRateLimiting();

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
