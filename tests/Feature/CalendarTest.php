<?php

use App\Models\Subscription;
use App\Models\User;

it('shows the authenticated users subscriptions in the selected calendar month', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Subscription::query()->create([
        'user_id' => $user->id,
        'name' => 'Monthly server',
        'amount_cents' => 500,
        'currency' => 'USD',
        'billing_cycle' => 'month',
        'next_due_on' => '2026-06-03',
    ]);
    Subscription::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Hidden subscription',
        'amount_cents' => 900,
        'currency' => 'USD',
        'billing_cycle' => 'month',
        'next_due_on' => '2026-06-03',
    ]);

    $response = $this->actingAs($user)->get(route('calendar', ['month' => '2026-06']));

    $response->assertOk();
    $response->assertSee('Monthly server');
    $response->assertDontSee('Hidden subscription');
});
