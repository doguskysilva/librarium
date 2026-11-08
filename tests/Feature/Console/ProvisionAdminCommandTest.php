<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config([
        'auth.admin.email' => '  DOGUSKY@PROTONMAIL.COM ',
        'auth.admin.password' => 'strong-admin-password',
    ]);
});

test('the command creates the administrator with a normalized email and hashed password', function () {
    $this->artisan('app:provision-admin')
        ->expectsOutput('Administrative account created.')
        ->assertSuccessful();

    $user = User::query()->firstOrFail();

    expect($user->email)->toBe('dogusky@protonmail.com')
        ->and(Hash::check('strong-admin-password', $user->password))->toBeTrue();
});

test('the command is idempotent and does not reset an existing password', function () {
    $user = User::factory()->create([
        'email' => 'dogusky@protonmail.com',
        'password' => 'existing-password',
    ]);

    $this->artisan('app:provision-admin')
        ->expectsOutput('Administrative account already exists.')
        ->assertSuccessful();

    expect(User::query()->count())->toBe(1)
        ->and(Hash::check('existing-password', $user->fresh()->password))->toBeTrue();
});

test('the command fails when the admin email is missing', function () {
    config(['auth.admin.email' => '']);

    $this->artisan('app:provision-admin')
        ->expectsOutput('ADMIN_EMAIL is required.')
        ->assertFailed();
});

test('the command fails when the admin password is missing', function () {
    config(['auth.admin.password' => '']);

    $this->artisan('app:provision-admin')
        ->expectsOutput('ADMIN_PASSWORD is required.')
        ->assertFailed();
});
