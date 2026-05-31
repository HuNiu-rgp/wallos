<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\UserNotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SystemSettingController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Edit', [
            'settings' => [
                ...SystemSetting::values(),
                ...UserNotificationSetting::valuesFor($request->user()),
            ],
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $globalRules = [
            'site_name' => ['required', 'string', 'max:100'],
            'site_logo_url' => ['nullable', 'string', 'max:2048'],
            'registration_enabled' => ['boolean'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_notification_days' => ['required', 'integer', 'min:0', 'max:365'],
            'timezone' => ['required', 'timezone'],
        ];
        $notificationRules = [
            'smtp_enabled' => ['boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:none,tls,ssl'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            'smtp_notification_email' => ['nullable', 'email', 'max:255'],
            'telegram_enabled' => ['boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'webhook_enabled' => ['boolean'],
            'webhook_method' => ['nullable', 'in:POST,PUT,PATCH'],
            'webhook_url' => ['nullable', 'url', 'max:2048'],
            'webhook_headers' => ['nullable', 'json', 'max:10000'],
            'webhook_payload' => ['nullable', 'json', 'max:20000'],
            'webhook_cancellation_payload' => ['nullable', 'json', 'max:20000'],
            'webhook_ignore_ssl_errors' => ['boolean'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ];
        $validated = $request->validate([
            ...($request->user()->isAdmin() ? $globalRules : []),
            ...$notificationRules,
        ]);

        foreach (array_intersect_key($validated, $notificationRules) as $key => $value) {
            UserNotificationSetting::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value],
            );
        }

        foreach (array_intersect_key($validated, $globalRules) as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value],
            );
        }

        return back()->with('success', __('Settings saved.'));
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->smtpValidationRules());

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $validated['smtp_host'],
            'mail.mailers.smtp.port' => (int) $validated['smtp_port'],
            'mail.mailers.smtp.username' => $validated['smtp_username'] ?: null,
            'mail.mailers.smtp.password' => $validated['smtp_password'] ?: null,
            'mail.mailers.smtp.scheme' => SystemSetting::smtpScheme($validated['smtp_encryption']),
            'mail.from.address' => $validated['smtp_from_address'],
            'mail.from.name' => $validated['smtp_from_name'] ?: SystemSetting::value('site_name'),
        ]);

        Mail::purge('smtp');

        try {
            Mail::raw('This is a test notification email from Wallos.', function ($message) use ($validated) {
                $message
                    ->to($validated['smtp_notification_email'])
                    ->subject('Wallos SMTP test');
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('Test email failed: :message', ['message' => $exception->getMessage()]));
        }

        return back()->with('success', __('Test email sent.'));
    }

    public function testTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telegram_bot_token' => ['required', 'string', 'max:255'],
            'telegram_chat_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            Http::timeout(10)
                ->post('https://api.telegram.org/bot'.$validated['telegram_bot_token'].'/sendMessage', [
                    'chat_id' => $validated['telegram_chat_id'],
                    'text' => 'Wallos Telegram test notification.',
                ])
                ->throw();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('Telegram test failed: :message', ['message' => $exception->getMessage()]));
        }

        return back()->with('success', __('Telegram test sent.'));
    }

    public function testWebhook(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'webhook_method' => ['required', 'in:POST,PUT,PATCH'],
            'webhook_url' => ['required', 'url', 'max:2048'],
            'webhook_headers' => ['nullable', 'json', 'max:10000'],
            'webhook_payload' => ['required', 'json', 'max:20000'],
            'webhook_ignore_ssl_errors' => ['boolean'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);
        $payload = json_decode($validated['webhook_payload'], true);
        $requestBuilder = Http::timeout(10);

        if ($validated['webhook_ignore_ssl_errors'] ?? false) {
            $requestBuilder = $requestBuilder->withoutVerifying();
        }

        if ($validated['webhook_headers'] ?? null) {
            $requestBuilder = $requestBuilder->withHeaders(json_decode($validated['webhook_headers'], true));
        }

        if ($validated['webhook_secret'] ?? null) {
            $requestBuilder = $requestBuilder->withHeaders([
                'X-Wallos-Signature' => hash_hmac('sha256', json_encode($payload), $validated['webhook_secret']),
            ]);
        }

        try {
            $requestBuilder->send($validated['webhook_method'], $validated['webhook_url'], [
                'json' => $payload,
            ])->throw();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('Webhook test failed: :message', ['message' => $exception->getMessage()]));
        }

        return back()->with('success', __('Webhook test sent.'));
    }

    private function smtpValidationRules(): array
    {
        return [
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['required', 'in:none,tls,ssl'],
            'smtp_from_address' => ['required', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            'smtp_notification_email' => ['required', 'email', 'max:255'],
        ];
    }
}
