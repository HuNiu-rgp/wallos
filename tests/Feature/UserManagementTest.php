<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('allows the default administrator to manage users', function () {
    $admin = User::factory()->create(['email' => 'admin@qq.com', 'role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'role' => 'admin',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'alice@example.com')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->role)->toBe('admin');

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Alice Updated',
            'email' => 'alice@example.com',
            'role' => 'user',
            'password' => 'changed12',
            'password_confirmation' => 'changed12',
        ])
        ->assertRedirect();

    expect($user->refresh()->name)->toBe('Alice Updated');
    expect(Hash::check('changed12', $user->password))->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect();

    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeFalse();
});

it('blocks regular users from user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

it('allows an administrator with a custom email to access user management', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();
});

it('protects the default administrator from deletion and email changes', function () {
    $admin = User::factory()->create(['email' => 'admin@qq.com', 'role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('users.update', $admin), [
            'name' => 'Admin',
            'email' => 'changed@example.com',
            'role' => 'user',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect();

    expect($admin->refresh()->email)->toBe('admin@qq.com');
    expect($admin->role)->toBe('admin');

    $this->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertStatus(422);
});
