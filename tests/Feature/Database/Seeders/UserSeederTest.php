<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('configured admin credentials seed a user', function () {
    config([
        'auth.admin.email' => 'Admin@Example.test',
        'auth.admin.password' => 'configured-password',
    ]);

    $this->seed(UserSeeder::class);

    $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

    expect($user->name)->toBe('Librarium Administrator')
        ->and(Hash::check('configured-password', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertDatabaseCount('users', 1);
});

test('default credentials are used when admin configuration is empty', function () {
    config([
        'auth.admin.email' => '',
        'auth.admin.password' => '',
    ]);

    $this->seed(UserSeeder::class);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect(Hash::check('password', $user->password))->toBeTrue();
});

test('database seeding calls the user seeder', function () {
    config([
        'auth.admin.email' => '',
        'auth.admin.password' => '',
    ]);

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});
