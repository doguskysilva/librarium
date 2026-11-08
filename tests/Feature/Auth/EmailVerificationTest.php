<?php

use Illuminate\Support\Facades\Route;

test('email verification routes are not registered', function () {
    expect(Route::has('verification.notice'))->toBeFalse()
        ->and(Route::has('verification.verify'))->toBeFalse()
        ->and(Route::has('verification.send'))->toBeFalse();
});

test('email verification endpoints are unavailable without mail delivery', function () {
    $this->get('/email/verify')->assertNotFound();

    $this->post('/email/verification-notification')
        ->assertNotFound();
});
