<?php

namespace App\Services;

use App\Mail\SubscriptionReminder;
use App\Models\Subscription;
use App\Models\SubscriptionNotificationDelivery;
use App\Models\SystemSetting;
use App\Models\UserNotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubscriptionNotificationService
{
    public function sendDueReminders(): array
    {
        $settings = SystemSetting::values();
        $timezone = $settings['timezone'] ?: config('app.timezone');
        $today = now($timezone)->startOfDay();
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        Subscription::query()
            ->with(['category:id,name', 'user.notificationSettings'])
            ->where('is_active', true)
            ->where('notification_enabled', true)
            ->whereDate('next_due_on', '>=', $today->toDateString())
            ->whereDate('next_due_on', '<=', $today->copy()->addDays(365)->toDateString())
            ->orderBy('next_due_on')
            ->each(function (Subscription $subscription) use ($settings, $today, &$result): void {
                $notificationSettings = [
                    ...UserNotificationSetting::valuesFor($subscription->user),
                    'site_name' => $settings['site_name'],
                ];
                $channels = $this->enabledChannels($notificationSettings);
                $daysUntilPayment = $today->diffInDays($subscription->next_due_on->copy()->startOfDay(), false);
                $notificationDays = $subscription->notification_days_before ?? (int) $settings['default_notification_days'];

                if ($channels === [] || $daysUntilPayment > $notificationDays) {
                    return;
                }

                foreach ($channels as $channel) {
                    if ($this->wasSent($subscription, $channel)) {
                        $result['skipped']++;

                        continue;
                    }

                    try {
                        $this->{'send'.ucfirst($channel)}($subscription, $notificationSettings, $daysUntilPayment);
                        $this->recordDelivery($subscription, $channel);
                        $result['sent']++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $result['failed']++;
                    }
                }
            });

        return $result;
    }

    private function enabledChannels(array $settings): array
    {
        return array_values(array_filter(['email', 'telegram', 'webhook'], function (string $channel) use ($settings): bool {
            return $settings[$channel === 'email' ? 'smtp_enabled' : $channel.'_enabled'] === '1';
        }));
    }

    private function sendEmail(Subscription $subscription, array $settings, int $daysUntilPayment): void
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
        Mail::to($settings['smtp_notification_email'])
            ->send(new SubscriptionReminder($subscription, $this->message($subscription, $daysUntilPayment)));
    }

    private function sendTelegram(Subscription $subscription, array $settings, int $daysUntilPayment): void
    {
        Http::timeout(10)
            ->post('https://api.telegram.org/bot'.$settings['telegram_bot_token'].'/sendMessage', [
                'chat_id' => $settings['telegram_chat_id'],
                'text' => $this->message($subscription, $daysUntilPayment),
            ])
            ->throw();
    }

    private function sendWebhook(Subscription $subscription, array $settings, int $daysUntilPayment): void
    {
        $payload = json_decode($this->replaceVariables($settings['webhook_payload'], $subscription, $daysUntilPayment), true, 512, JSON_THROW_ON_ERROR);
        $request = Http::timeout(10);

        if ($settings['webhook_ignore_ssl_errors'] === '1') {
            $request = $request->withoutVerifying();
        }

        if ($settings['webhook_headers']) {
            $request = $request->withHeaders(json_decode($settings['webhook_headers'], true, 512, JSON_THROW_ON_ERROR));
        }

        if ($settings['webhook_secret']) {
            $request = $request->withHeaders([
                'X-Wallos-Signature' => hash_hmac('sha256', json_encode($payload), $settings['webhook_secret']),
            ]);
        }

        $request->send($settings['webhook_method'], $settings['webhook_url'], ['json' => $payload])->throw();
    }

    private function message(Subscription $subscription, int $daysUntilPayment): string
    {
        return implode("\n", [
            '订阅到期提醒',
            '订阅名称：'.$subscription->name,
            '金额：'.$this->price($subscription),
            '付款人：'.($subscription->payer_name ?: '-'),
            '下次支付日期：'.$subscription->next_due_on->format('Y-m-d'),
            '距离付款：'.$daysUntilPayment.' 天',
            '续费链接：'.($subscription->link_url ?: '-'),
        ]);
    }

    private function replaceVariables(string $template, Subscription $subscription, int $daysUntilPayment): string
    {
        $variables = [
            'subscription_name' => $subscription->name,
            'subscription_price' => $this->price($subscription),
            'subscription_currency' => $subscription->currency,
            'subscription_category' => $subscription->category?->name ?? '未分类',
            'subscription_date' => $subscription->next_due_on->format('Y-m-d'),
            'subscription_payer' => $subscription->payer_name ?? '',
            'subscription_days_until_payment' => (string) $daysUntilPayment,
            'subscription_notes' => $subscription->notes ?? '',
            'subscription_url' => $subscription->link_url ?? '',
        ];

        foreach ($variables as $key => $value) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $template = str_replace('{{'.$key.'}}', substr($encoded, 1, -1), $template);
        }

        return $template;
    }

    private function price(Subscription $subscription): string
    {
        return number_format($subscription->amount_cents / 100, 2).' '.$subscription->currency;
    }

    private function wasSent(Subscription $subscription, string $channel): bool
    {
        return SubscriptionNotificationDelivery::query()
            ->where('subscription_id', $subscription->id)
            ->where('channel', $channel)
            ->whereDate('next_due_on', $subscription->next_due_on)
            ->exists();
    }

    private function recordDelivery(Subscription $subscription, string $channel): void
    {
        SubscriptionNotificationDelivery::query()->create([
            'subscription_id' => $subscription->id,
            'channel' => $channel,
            'next_due_on' => $subscription->next_due_on,
            'sent_at' => now(),
        ]);
    }
}
