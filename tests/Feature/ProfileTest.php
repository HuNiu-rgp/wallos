<?php

use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Models\SystemSetting;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('user can update their notification destinations', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.notifications.update'), [
            'smtp_notification_email' => 'notify@example.com',
            'telegram_chat_id' => '12345',
            'webhook_enabled' => true,
            'webhook_method' => 'POST',
            'webhook_url' => 'https://example.com/hooks/wallos',
            'webhook_payload' => '{"event":"subscription.reminder"}',
            'webhook_ignore_ssl_errors' => false,
        ])
        ->assertRedirect();

    $settings = UserNotificationSetting::valuesFor($user);

    expect($settings['smtp_notification_email'])->toBe('notify@example.com');
    expect($settings['telegram_chat_id'])->toBe('12345');
    expect($settings['webhook_enabled'])->toBe('1');
});

test('user can send a signed webhook test', function () {
    Http::fake(['https://example.com/hooks/wallos' => Http::response()]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.notifications.test-webhook'), [
            'webhook_enabled' => true,
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

test('user email test uses the administrator smtp provider', function () {
    Mail::fake();
    $user = User::factory()->create();
    SystemSetting::query()->updateOrCreate(['key' => 'smtp_enabled'], ['value' => '1']);
    SystemSetting::query()->updateOrCreate(['key' => 'smtp_host'], ['value' => 'smtp.provider.example']);

    $this->actingAs($user)
        ->post(route('profile.notifications.test-email'), [
            'smtp_notification_email' => 'notify@example.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(config('mail.mailers.smtp.host'))->toBe('smtp.provider.example');
});

test('user telegram test uses the administrator bot token', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    $user = User::factory()->create();
    SystemSetting::query()->updateOrCreate(['key' => 'telegram_enabled'], ['value' => '1']);
    SystemSetting::query()->updateOrCreate(['key' => 'telegram_bot_token'], ['value' => 'provider-token']);

    $this->actingAs($user)
        ->post(route('profile.notifications.test-telegram'), [
            'telegram_chat_id' => '67890',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/botprovider-token/sendMessage'
        && $request['chat_id'] === '67890');
});
