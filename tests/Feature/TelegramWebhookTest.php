<?php

use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    foreach ([
        'telegram_enabled' => '1',
        'telegram_bot_token' => 'provider-token',
        'telegram_webhook_secret' => 'webhook-secret',
    ] as $key => $value) {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
});

it('returns the chat id when a telegram user starts the bot', function () {
    $this->postJson(route('telegram.webhook'), telegramUpdate('778899', '/start'), [
        'X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret',
    ])->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/botprovider-token/sendMessage'
        && $request['chat_id'] === '778899'
        && str_contains($request['text'], '你的 Chat ID：778899'));
});

it('lists only subscriptions belonging to the bound telegram user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    UserNotificationSetting::query()->create([
        'user_id' => $user->id,
        'key' => 'telegram_chat_id',
        'value' => '778899',
    ]);
    createTelegramSubscription($user, 'My VPS');
    createTelegramSubscription($otherUser, 'Private subscription');

    $this->postJson(route('telegram.webhook'), telegramUpdate('778899', '/subscriptions'), [
        'X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret',
    ])->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/botprovider-token/sendMessage'
        && str_contains($request['text'], 'My VPS')
        && ! str_contains($request['text'], 'Private subscription'));
});

it('rejects telegram webhook requests with an invalid secret', function () {
    $this->postJson(route('telegram.webhook'), telegramUpdate('778899', '/help'), [
        'X-Telegram-Bot-Api-Secret-Token' => 'invalid',
    ])->assertForbidden();

    Http::assertNothingSent();
});

function telegramUpdate(string $chatId, string $text): array
{
    return [
        'message' => [
            'chat' => ['id' => $chatId],
            'text' => $text,
        ],
    ];
}

function createTelegramSubscription(User $user, string $name): void
{
    Subscription::query()->create([
        'user_id' => $user->id,
        'name' => $name,
        'amount_cents' => 500,
        'currency' => 'USD',
        'billing_interval' => 1,
        'billing_cycle' => 'month',
        'next_due_on' => now()->addDays(3)->toDateString(),
        'notification_enabled' => true,
        'is_active' => true,
    ]);
}
