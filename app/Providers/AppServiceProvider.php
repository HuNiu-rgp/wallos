<?php

namespace App\Providers;

use App\Models\SystemSetting;
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

        config([
            'app.timezone' => $settings['timezone'],
            'mail.default' => $settings['smtp_enabled'] === '1' && $settings['smtp_host'] ? 'smtp' : config('mail.default'),
            'mail.mailers.smtp.host' => $settings['smtp_host'] ?: config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => (int) $settings['smtp_port'],
            'mail.mailers.smtp.username' => $settings['smtp_username'] ?: null,
            'mail.mailers.smtp.password' => $settings['smtp_password'] ?: null,
            'mail.mailers.smtp.scheme' => SystemSetting::smtpScheme($settings['smtp_encryption']),
            'mail.from.address' => $settings['smtp_from_address'] ?: config('mail.from.address'),
            'mail.from.name' => $settings['smtp_from_name'] ?: config('mail.from.name'),
        ]);

        date_default_timezone_set($settings['timezone']);

        Vite::prefetch(concurrency: 3);
    }
}
