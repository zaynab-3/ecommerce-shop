<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        RateLimiter::for('admin-login', fn (Request $r) => [Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()), Limit::perMinute(20)->by($r->ip())]);
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('stock', fn (Request $r) => Limit::perMinute(30)->by($r->ip()));
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));
        RateLimiter::for('catalog-sync', fn (Request $r) => Limit::perMinute(180)->by($r->ip()));
        RateLimiter::for('cart', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
    }
}
