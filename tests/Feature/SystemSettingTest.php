<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

it('blocks registration when disabled in system settings', function () {
    SystemSetting::query()->create([
        'key' => 'registration_enabled',
        'value' => '0',
    ]);

    $this->get(route('register'))->assertNotFound();

    $this->post(route('register'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

it('allows the default administrator to update system settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('settings.update'), [
            ...SystemSetting::defaults(),
            'site_name' => 'My Wallos',
            'registration_enabled' => false,
        ])
        ->assertRedirect();

    expect(SystemSetting::value('site_name'))->toBe('My Wallos');
    expect(SystemSetting::enabled('registration_enabled'))->toBeFalse();
});

it('allows a relative site logo path', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('settings.update'), [
            ...SystemSetting::defaults(),
            'site_logo_url' => '/favicon.ico',
        ])
        ->assertRedirect();

    expect(SystemSetting::value('site_logo_url'))->toBe('/favicon.ico');
});

it('blocks regular users from system settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.edit'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('settings.update'), SystemSetting::defaults())
        ->assertForbidden();
});

it('allows the default administrator to send a test email', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('settings.test-email'), [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'mailer@example.com',
            'smtp_password' => 'secret',
            'smtp_encryption' => 'tls',
            'smtp_from_address' => 'mailer@example.com',
            'smtp_from_name' => 'Wallos',
            'smtp_notification_email' => 'notify@example.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

it('validates the notification email before sending a test email', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('settings.test-email'), [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_from_address' => 'mailer@example.com',
            'smtp_notification_email' => 'invalid',
        ])
        ->assertSessionHasErrors('smtp_notification_email');
});

it('maps smtp encryption options to supported mailer schemes', function () {
    expect(SystemSetting::smtpScheme('none'))->toBeNull();
    expect(SystemSetting::smtpScheme('tls'))->toBe('smtp');
    expect(SystemSetting::smtpScheme('ssl'))->toBe('smtps');
});

it('allows the default administrator to send a telegram test message', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('settings.test-telegram'), [
            'telegram_bot_token' => 'token',
            'telegram_chat_id' => '12345',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottoken/sendMessage'
        && $request['chat_id'] === '12345');
});

it('allows the default administrator to register the telegram webhook', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    URL::forceRootUrl('https://wallos.example.com');
    URL::forceScheme('https');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('settings.register-telegram-webhook'), [
            'telegram_bot_token' => 'token',
            'telegram_bot_name' => 'wallos_bot',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(SystemSetting::value('telegram_enabled'))->toBe('1');
    expect(SystemSetting::value('telegram_bot_name'))->toBe('wallos_bot');
    expect(SystemSetting::value('telegram_webhook_secret'))->not->toBeEmpty();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottoken/setWebhook'
        && $request['url'] === 'https://wallos.example.com/telegram/webhook'
        && $request['secret_token'] === SystemSetting::value('telegram_webhook_secret'));
});

it('normalizes the telegram bot username when saving settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->patch(route('settings.update'), [
            ...SystemSetting::defaults(),
            'telegram_bot_name' => '@wallos_bot',
        ])
        ->assertRedirect();

    expect(SystemSetting::value('telegram_bot_name'))->toBe('wallos_bot');
});

it('shows the telegram webhook status and latest delivery error', function () {
    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => [
                'url' => 'https://wallos.example.com/telegram/webhook',
                'pending_update_count' => 2,
                'last_error_message' => 'Wrong response from the webhook: 419',
            ],
        ]),
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('settings.telegram-webhook-status'), [
            'telegram_bot_token' => 'token',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Webhook URL：https://wallos.example.com/telegram/webhook；待处理更新：2；最近错误：Wrong response from the webhook: 419');
});
