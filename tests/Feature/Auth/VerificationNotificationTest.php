<?php

use Illuminate\Support\Facades\Route;

test('verification notification route is not registered', function () {
    expect(Route::has('verification.send'))->toBeFalse();
});
