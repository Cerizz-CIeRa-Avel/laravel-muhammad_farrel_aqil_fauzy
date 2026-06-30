<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Rate Limiter untuk Login: Maksimal 5x per menit per IP/Email
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email ?: $request->ip());
        });
    }
}