<?php

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('creates a subscription from the modal payload', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('subscriptions.store'), [
            'name' => 'Netflix',
            'amount' => '19.99',
            'currency' => 'USD',
            'billing_interval' => 1,
            'billing_cycle' => 'month',
            'start_on' => '2026-05-30',
            'next_due_on' => '2026-06-30',
            'payment_method' => 'PayPal',
            'payer_name' => 'Bob',
            'auto_renew' => 1,
            'notification_enabled' => 1,
            'notification_days_before' => 3,
            'link_url' => 'https://example.com',
            'logo_url' => 'https://example.com/netflix.png',
            'is_active' => 1,
            'notes' => 'Family plan',
        ]);

    $response->assertRedirect();

    expect(Subscription::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(Subscription::first())
        ->name->toBe('Netflix')
        ->amount_cents->toBe(1999)
        ->currency->toBe('USD')
        ->logo_path->toBe('https://example.com/netflix.png')
        ->logo_url->toBe('https://example.com/netflix.png')
        ->billing_interval->toBe(1)
        ->billing_cycle->toBe('month')
        ->payment_method->toBe('PayPal')
        ->payer_name->toBe('Bob')
        ->notification_days_before->toBe(3);
});

it('imports and exports subscriptions using the wallos json format', function () {
    $user = User::factory()->create();
    $json = json_encode([[
        'Name' => '新加坡-Vultr',
        'Payment Cycle' => 'Monthly',
        'Next Payment' => '2026-06-24',
        'Renewal' => 'Automatic',
        'Category' => 'Servers',
        'Payment Method' => 'PayPal',
        'Paid By' => 'Bob',
        'Price' => '$5',
        'Notes' => 'Example',
        'URL' => 'https://example.com/?a=1&amp;b=2',
        'State' => 'Enabled',
        'Notifications' => 'Enabled',
        'Cancellation Date' => '',
        'Active' => 'Yes',
    ]], JSON_UNESCAPED_UNICODE);

    $response = $this
        ->actingAs($user)
        ->post(route('subscriptions.import'), [
            'file' => UploadedFile::fake()->createWithContent('subscriptions.json', $json),
        ]);

    $response->assertRedirect();

    $subscription = Subscription::query()->where('user_id', $user->id)->firstOrFail();

    expect($subscription)
        ->name->toBe('新加坡-Vultr')
        ->amount_cents->toBe(500)
        ->currency->toBe('USD')
        ->billing_cycle->toBe('month')
        ->link_url->toBe('https://example.com/?a=1&b=2')
        ->notification_enabled->toBeTrue();

    $export = $this->actingAs($user)->get(route('subscriptions.export'));
    $export->assertOk();
    $export->assertHeader('content-disposition', 'attachment; filename=subscriptions.json');

    expect(json_decode($export->streamedContent(), true)[0])
        ->toMatchArray([
            'Name' => '新加坡-Vultr',
            'Payment Cycle' => 'Monthly',
            'Price' => '$5',
            'Category' => 'Servers',
        ]);
});

it('stores safe svg subscription logos and exposes an image preview', function () {
    $user = User::factory()->create();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';

    $this->actingAs($user)
        ->post(route('subscriptions.store'), [
            'name' => 'SVG Logo',
            'logo_url' => $svg,
            'amount' => '10',
            'currency' => 'USD',
            'billing_interval' => 1,
            'billing_cycle' => 'month',
            'next_due_on' => '2026-06-30',
            'auto_renew' => 1,
            'notification_enabled' => 0,
            'is_active' => 1,
        ])
        ->assertRedirect();

    $subscription = Subscription::query()->where('user_id', $user->id)->firstOrFail();

    expect($subscription->logo_path)->toBe($svg);
    expect($subscription->logo_input)->toBe($svg);
    expect($subscription->logo_url)->toStartWith('data:image/svg+xml;base64,');
});

it('rejects unsafe svg subscription logos', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('subscriptions.store'), [
            'name' => 'Unsafe SVG',
            'logo_url' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            'amount' => '10',
            'currency' => 'USD',
            'billing_interval' => 1,
            'billing_cycle' => 'month',
            'next_due_on' => '2026-06-30',
            'auto_renew' => 1,
            'notification_enabled' => 0,
            'is_active' => 1,
        ])
        ->assertSessionHasErrors('logo_url');
});
