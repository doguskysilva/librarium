<?php

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('returns Google Books metadata for an ISBN', function () {
    config([
        'services.google_books.api_key' => 'google-test-key',
        'services.open_library.email' => 'librarian@example.test',
    ]);

    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response([
            'totalItems' => 1,
            'items' => [[
                'volumeInfo' => [
                    'title' => 'The Example Book',
                    'subtitle' => 'A Test Edition',
                    'authors' => ['Ada Author', 'Bea Writer'],
                    'industryIdentifiers' => [
                        ['type' => 'ISBN_10', 'identifier' => '1234567890'],
                        ['type' => 'ISBN_13', 'identifier' => '9781234567897'],
                    ],
                    'publisher' => 'Example Press',
                    'publishedDate' => '2024-05-12',
                    'language' => 'en',
                    'description' => 'A useful description.',
                    'imageLinks' => [
                        'thumbnail' => 'http://books.example.test/cover.jpg',
                    ],
                ],
            ]],
        ]),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'title' => 'An Open Book',
                'author_name' => ['Casey Writer'],
                'isbn' => ['9781234567897'],
                'publisher' => ['Open Press'],
                'first_publish_year' => 1998,
                'language' => ['eng'],
                'cover_i' => 12345,
            ]],
        ]),
    ]);

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => '9781234567897',
    ]);

    $response->assertOk()
        ->assertJsonPath('google_books.metadata.provider', 'google_books')
        ->assertJsonPath('google_books.metadata.title', 'The Example Book')
        ->assertJsonPath('google_books.metadata.authors', ['Ada Author', 'Bea Writer'])
        ->assertJsonPath('google_books.metadata.isbn', '9781234567897')
        ->assertJsonPath('google_books.metadata.publicationYear', 2024)
        ->assertJsonPath('google_books.metadata.coverUrl', 'https://books.example.test/cover.jpg')
        ->assertJsonPath('google_books.error', null)
        ->assertJsonPath('open_library.metadata.provider', 'open_library')
        ->assertJsonPath('open_library.metadata.title', 'An Open Book')
        ->assertJsonPath('open_library.error', null);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://www.googleapis.com/books/v1/volumes?')
        && $request['q'] === 'isbn:9781234567897'
        && $request['key'] === 'google-test-key');
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'openlibrary.org/search.json')
        && $request['isbn'] === '9781234567897'
        && $request->hasHeader('User-Agent', 'Librarium (librarian@example.test)'));
    Http::assertSentCount(2);
});

it('returns a not-found result for each source when neither has a match', function () {
    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response(['totalItems' => 0]),
        'openlibrary.org/search.json*' => Http::response(['docs' => []]),
    ]);

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => '9781234567897',
    ]);

    $response->assertOk()
        ->assertJsonPath('google_books.metadata', null)
        ->assertJsonPath('google_books.error', null)
        ->assertJsonPath('open_library.metadata', null)
        ->assertJsonPath('open_library.error', null);

    Http::assertSentCount(2);
});

it('queries providers using ISBN-13 when given an ISBN-10', function () {
    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response(['totalItems' => 0]),
        'openlibrary.org/search.json*' => Http::response(['docs' => []]),
    ]);

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => '0-306-40615-2',
    ]);

    $response->assertOk();
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'googleapis.com')
        && $request['q'] === 'isbn:9780306406157');
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'openlibrary.org')
        && $request['isbn'] === '9780306406157');
});

it('does not claim the requested ISBN when a provider omits its identifiers', function () {
    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response([
            'totalItems' => 1,
            'items' => [[
                'volumeInfo' => [
                    'title' => 'A Book',
                ],
            ]],
        ]),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'title' => 'A Book',
            ]],
        ]),
    ]);

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => '9781234567897',
    ]);

    $response->assertOk()
        ->assertJsonPath('google_books.metadata.isbn', null)
        ->assertJsonPath('open_library.metadata.isbn', null);
});

it('reports one provider failure while returning the other provider result', function () {
    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake([
        'www.googleapis.com/books/v1/volumes*' => Http::response(['error' => 'Unavailable'], 503),
        'openlibrary.org/search.json*' => Http::response([
            'docs' => [[
                'title' => 'An Open Book',
                'author_name' => ['Casey Writer'],
                'cover_i' => 12345,
            ]],
        ]),
    ]);

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => '9781234567897',
    ]);

    $response->assertOk()
        ->assertJsonPath('google_books.metadata', null)
        ->assertJsonPath('google_books.error', 'Provider returned HTTP 503.')
        ->assertJsonPath('open_library.metadata.title', 'An Open Book')
        ->assertJsonPath('open_library.error', null);
});

it('rejects invalid ISBN input without querying providers', function () {
    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake();

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => 'not-an-isbn',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('isbn');

    Http::assertNothingSent();
});

it('rejects ISBN values with an invalid check digit', function () {
    $user = User::factory()->create();

    Http::preventStrayRequests();
    Http::fake();

    $response = $this->actingAs($user)->postJson(route('books.metadata.lookup'), [
        'isbn' => '9780306406158',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('isbn');

    Http::assertNothingSent();
});

it('requires authentication to look up book metadata', function () {
    Http::preventStrayRequests();
    Http::fake();

    $response = $this->postJson(route('books.metadata.lookup'), [
        'isbn' => '9781234567897',
    ]);

    $response->assertUnauthorized();
    Http::assertNothingSent();
});
