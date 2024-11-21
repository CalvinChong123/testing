<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // NEXUS: Api Domain
            // Route::middleware('api')
            // 	->name("api.")
            //     ->domain(config('app.api_url'))
            //     ->group(base_path('routes/api.php'));

            // NEXUS: Admin Domain
            Route::middleware('api')
                ->domain(config('app.admin_url'))
                ->group(base_path('routes/admin_api.php'));

            Route::middleware('web')
                ->domain(config('app.admin_url'))
                ->group(base_path('routes/admin_web.php'));

            Route::fallback(function () {
                if (request()->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The route you looking at is not available.',
                    ], 404);
                }

                abort(404);
            });
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60);
        });
    }
}
