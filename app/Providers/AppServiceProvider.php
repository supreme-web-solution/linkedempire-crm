<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('v2-auth', function (Request $request) {
            $key = (string) $request->ip().'|'.(string) $request->input('email');

            return [
                Limit::perMinute(10)->by($key),
            ];
        });

        RateLimiter::for('v2-extension', function (Request $request) {
            $user = $request->attributes->get('v2User');
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return [
                Limit::perMinute(120)->by($key),
            ];
        });
    }
}
