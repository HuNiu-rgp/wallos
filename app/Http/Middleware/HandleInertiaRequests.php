<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = SystemSetting::values();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'isAdmin' => $request->user()?->isAdmin() ?? false,
            ],
            'site' => [
                'name' => $settings['site_name'],
                'logoUrl' => $settings['site_logo_url'],
                'registrationEnabled' => $settings['registration_enabled'] === '1',
                'defaultCurrency' => $settings['default_currency'],
                'defaultNotificationDays' => (int) $settings['default_notification_days'],
                'timezone' => $settings['timezone'],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
