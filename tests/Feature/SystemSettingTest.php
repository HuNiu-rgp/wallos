<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

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

it('allows regular users to update only their notification settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.update'), [
            'site_name' => 'Blocked',
            'smtp_enabled' => true,
            'telegram_enabled' => false,
            'webhook_enabled' => false,
            'webhook_method' => 'POST',
        ])
        ->assertRedirect();

    expect(SystemSetting::value('site_name'))->toBe('Wallos');
    expect(UserNotificationSetting::valuesFor($user)['smtp_enabled'])->toBe('1');
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

it('allows the default administrator to send a signed webhook test', function () {
    Http::fake(['https://example.com/hooks/wallos' => Http::response()]);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('settings.test-webhook'), [
            'webhook_method' => 'PUT',
            'webhook_url' => 'https://example.com/hooks/wallos',
            'webhook_headers' => '{"X-Custom-Header":"wallos"}',
            'webhook_payload' => '{"event":"wallos.test","message":"Test"}',
            'webhook_ignore_ssl_errors' => true,
            'webhook_secret' => 'secret',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://example.com/hooks/wallos'
        && $request->method() === 'PUT'
        && $request['event'] === 'wallos.test'
        && $request->hasHeader('X-Custom-Header', 'wallos')
        && $request->hasHeader('X-Wallos-Signature'));
});
