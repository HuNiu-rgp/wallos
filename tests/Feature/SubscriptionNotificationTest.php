<?php

use App\Models\Subscription;
use App\Models\SubscriptionNotificationDelivery;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('sends enabled subscription reminder channels once per due date', function () {
    Mail::fake();
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
        'https://example.com/hooks/wallos' => Http::response(),
    ]);
    $this->travelTo(now()->startOfDay());
    $subscription = createNotifiableSubscription(['next_due_on' => now()->addDays(3)->toDateString()]);
    createNotificationSettings();

    expect(Artisan::call('subscriptions:notify'))->toBe(0);
    expect(SubscriptionNotificationDelivery::query()->count())->toBe(3);
    Mail::assertSentCount(1);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottoken/sendMessage'
        && $request['chat_id'] === '12345');
    Http::assertSent(fn (Request $request) => $request->url() === 'https://example.com/hooks/wallos'
        && $request['name'] === $subscription->name
        && $request['days'] === '3');

    expect(Artisan::call('subscriptions:notify'))->toBe(0);
    expect(SubscriptionNotificationDelivery::query()->count())->toBe(3);
    Mail::assertSentCount(1);
    Http::assertSentCount(2);
});

it('does not notify subscriptions before their reminder window', function () {
    Mail::fake();
    $this->travelTo(now()->startOfDay());
    createNotifiableSubscription(['next_due_on' => now()->addDays(4)->toDateString()]);
    createNotificationSettings([
        'telegram_enabled' => '0',
        'webhook_enabled' => '0',
    ]);

    expect(Artisan::call('subscriptions:notify'))->toBe(0);
    expect(SubscriptionNotificationDelivery::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

function createNotifiableSubscription(array $attributes = []): Subscription
{
    return Subscription::query()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Example subscription',
        'amount_cents' => 1250,
        'currency' => 'USD',
        'billing_interval' => 1,
        'billing_cycle' => 'month',
        'next_due_on' => now()->addDays(3)->toDateString(),
        'notification_enabled' => true,
        'notification_days_before' => 3,
        'is_active' => true,
        ...$attributes,
    ]);
}

function createNotificationSettings(array $overrides = []): void
{
    $settings = [
        ...SystemSetting::defaults(),
        'smtp_enabled' => '1',
        'smtp_host' => 'smtp.example.com',
        'smtp_from_address' => 'mailer@example.com',
        'smtp_notification_email' => 'notify@example.com',
        'telegram_enabled' => '1',
        'telegram_bot_token' => 'token',
        'telegram_chat_id' => '12345',
        'webhook_enabled' => '1',
        'webhook_url' => 'https://example.com/hooks/wallos',
        ...$overrides,
    ];

    foreach ($settings as $key => $value) {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
