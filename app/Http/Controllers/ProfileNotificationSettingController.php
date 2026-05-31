<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\UserNotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProfileNotificationSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        foreach ($validated as $key => $value) {
            UserNotificationSetting::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value],
            );
        }

        return back()->with('success', __('Notification settings saved.'));
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'smtp_notification_email' => ['required', 'email', 'max:255'],
        ]);
        $settings = SystemSetting::values();

        abort_unless($settings['smtp_enabled'] === '1', 422, __('SMTP is disabled.'));
        $this->configureMail($settings);

        try {
            Mail::raw('这是一封来自 Wallos 的测试通知邮件。', function ($message) use ($validated) {
                $message->to($validated['smtp_notification_email'])->subject('Wallos 邮件通知测试');
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
            'telegram_chat_id' => ['required', 'string', 'max:255'],
        ]);
        $settings = SystemSetting::values();

        abort_unless($settings['telegram_enabled'] === '1' && $settings['telegram_bot_token'], 422, __('Telegram is disabled.'));

        try {
            Http::timeout(10)
                ->post('https://api.telegram.org/bot'.$settings['telegram_bot_token'].'/sendMessage', [
                    'chat_id' => $validated['telegram_chat_id'],
                    'text' => '这是一条来自 Wallos 的 Telegram 测试通知。',
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
            ...$this->rules(),
            'webhook_url' => ['required', 'url', 'max:2048'],
            'webhook_payload' => ['required', 'json', 'max:20000'],
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
            $requestBuilder->send($validated['webhook_method'], $validated['webhook_url'], ['json' => $payload])->throw();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', __('Webhook test failed: :message', ['message' => $exception->getMessage()]));
        }

        return back()->with('success', __('Webhook test sent.'));
    }

    private function rules(): array
    {
        return [
            'smtp_notification_email' => ['nullable', 'email', 'max:255'],
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
    }

    private function configureMail(array $settings): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['smtp_host'],
            'mail.mailers.smtp.port' => (int) $settings['smtp_port'],
            'mail.mailers.smtp.username' => $settings['smtp_username'] ?: null,
            'mail.mailers.smtp.password' => $settings['smtp_password'] ?: null,
            'mail.mailers.smtp.scheme' => SystemSetting::smtpScheme($settings['smtp_encryption']),
            'mail.from.address' => $settings['smtp_from_address'],
            'mail.from.name' => $settings['smtp_from_name'] ?: $settings['site_name'],
        ]);

        Mail::purge('smtp');
    }
}
