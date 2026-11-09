<?php

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Reading;
use App\Models\User;

test('guests are redirected when visiting a reading', function () {
    $book = Book::factory()->create();

    $this->get(route('books.readings.create', $book))->assertRedirect(route('login'));
});

test('authenticated users can create a queued reading with markdown content', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $response = $this->actingAs($user)->post(route('books.readings.store', $book), [
        'review' => '# First thoughts',
        'private_notes' => 'Remember the opening chapter.',
    ]);

    $reading = Reading::query()->first();

    $response->assertSessionHasNoErrors()->assertRedirect(route('readings.show', $reading));
    expect($reading)->not->toBeNull();
    $this->assertDatabaseHas('readings', [
        'id' => $reading->id,
        'book_id' => $book->id,
        'status' => ReadingStatus::Queued->value,
        'review' => '# First thoughts',
    ]);
});

test('a queued reading can start with an exact date', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create();

    $this->actingAs($user)->post(route('readings.start', $reading), [
        'date_precision' => 'exact',
        'date' => '2026-11-09',
    ])->assertRedirect();

    $reading->refresh();
    expect($reading->status)->toBe(ReadingStatus::Reading)
        ->and($reading->started_on?->toDateString())->toBe('2026-11-09')
        ->and($reading->started_year)->toBeNull();
});

test('a queued reading can start with a year-only date', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create();

    $this->actingAs($user)->post(route('readings.start', $reading), [
        'date_precision' => 'year',
        'year' => 2024,
    ])->assertRedirect();

    $this->assertDatabaseHas('readings', ['id' => $reading->id, 'started_on' => null, 'started_year' => 2024]);
});

test('reading transitions reject an incomplete date precision payload', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create();

    $this->actingAs($user)
        ->from(route('readings.show', $reading))
        ->post(route('readings.start', $reading), ['date_precision' => 'exact'])
        ->assertSessionHasErrors('date')
        ->assertRedirect(route('readings.show', $reading));

    $this->assertDatabaseHas('readings', ['id' => $reading->id, 'status' => ReadingStatus::Queued->value]);
});

test('a reading can be paused resumed and completed', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['status' => ReadingStatus::Reading, 'started_on' => '2026-11-01']);

    $this->actingAs($user)->post(route('readings.pause', $reading))->assertRedirect();
    expect($reading->refresh()->status)->toBe(ReadingStatus::Paused);

    $this->actingAs($user)->post(route('readings.resume', $reading))->assertRedirect();
    expect($reading->refresh()->status)->toBe(ReadingStatus::Reading);

    $this->actingAs($user)->post(route('readings.complete', $reading), [
        'date_precision' => 'year',
        'year' => 2026,
    ])->assertRedirect();

    $reading->refresh();
    expect($reading->status)->toBe(ReadingStatus::Completed)
        ->and($reading->finished_on)->toBeNull()
        ->and($reading->finished_year)->toBe(2026);
});

test('rating is only accepted for completed readings', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['status' => ReadingStatus::Reading]);

    $this->actingAs($user)
        ->from(route('readings.edit', $reading))
        ->put(route('readings.update', $reading), ['rating' => 5])
        ->assertSessionHasErrors('rating')
        ->assertRedirect(route('readings.edit', $reading));

    $this->assertDatabaseHas('readings', ['id' => $reading->id, 'rating' => null]);
});

test('terminal readings cannot be reopened through lifecycle actions', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create(['status' => ReadingStatus::Completed]);

    $this->actingAs($user)->post(route('readings.resume', $reading))->assertStatus(422);
    $this->assertDatabaseHas('readings', ['id' => $reading->id, 'status' => ReadingStatus::Completed->value]);
});

test('a book can have multiple reading attempts', function () {
    $book = Book::factory()->create();
    Reading::factory()->count(2)->for($book)->create();

    expect($book->readings)->toHaveCount(2);
});

test('book details include reading history newest first', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();
    $old = Reading::factory()->for($book)->create(['created_at' => now()->subDay()]);
    $new = Reading::factory()->for($book)->create(['created_at' => now()]);

    $this->actingAs($user)->get(route('books.show', $book))->assertInertia(fn ($page) => $page
        ->component('books/Show')
        ->where('book.readings.0.id', $new->id)
        ->where('book.readings.1.id', $old->id));
});

test('rendered markdown escapes raw html and unsafe links', function () {
    $user = User::factory()->create();
    $reading = Reading::factory()->create([
        'review' => '<script>alert(1)</script> [unsafe](javascript:alert(1))',
    ]);

    $this->actingAs($user)->get(route('readings.show', $reading))->assertInertia(fn ($page) => $page
        ->where('reading.review_html', fn (string $html): bool => str_contains($html, '&lt;script&gt;'))
        ->where('reading.review_html', fn (string $html): bool => ! str_contains($html, '<script>') && ! str_contains($html, 'href="javascript:')));
});
