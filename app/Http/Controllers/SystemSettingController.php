<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SystemSettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $this->authorizeAdmin($request);

        return Inertia::render('Settings/Edit', [
            'settings' => SystemSetting::values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_logo_url' => ['nullable', 'string', 'max:2048'],
            'registration_enabled' => ['boolean'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_notification_days' => ['required', 'integer', 'min:0', 'max:365'],
            'timezone' => ['required', 'timezone'],
            'smtp_enabled' => ['boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:none,tls,ssl'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            'telegram_enabled' => ['boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_bot_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value],
            );
        }

        return back()->with('success', __('Settings saved.'));
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            ...$this->smtpValidationRules(),
            'smtp_notification_email' => ['required', 'email', 'max:255'],
        ]);

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
        $this->authorizeAdmin($request);
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

    public function registerTelegramWebhook(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'telegram_bot_token' => ['required', 'string', 'max:255'],
            'telegram_bot_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);
        $secret = Str::random(48);
        $webhookUrl = route('telegram.webhook');

        abort_unless(str_starts_with($webhookUrl, 'https://'), 422, __('Telegram webhook requires an HTTPS APP_URL.'));

        try {
            Http::timeout(10)
                ->post('https://api.telegram.org/bot'.$validated['telegram_bot_token'].'/setWebhook', [
                    'url' => $webhookUrl,
                    'secret_token' => $secret,
                    'allowed_updates' => ['message'],
                ])
                ->throw();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('Telegram webhook registration failed: :message', ['message' => $exception->getMessage()]));
        }

        foreach ([
            'telegram_enabled' => '1',
            'telegram_bot_token' => $validated['telegram_bot_token'],
            'telegram_bot_name' => $validated['telegram_bot_name'],
            'telegram_webhook_secret' => $secret,
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', __('Telegram webhook registered.'));
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
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }
}
