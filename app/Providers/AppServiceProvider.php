<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        $settings = SystemSetting::values();
        $appUrl = (string) config('app.url');

        if (str_starts_with(strtolower($appUrl), 'https://')) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
            URL::forceScheme('https');
        }

        config([
            'app.timezone' => $settings['timezone'],
        ]);

        date_default_timezone_set($settings['timezone']);

        Vite::prefetch(concurrency: 3);
    }
}
