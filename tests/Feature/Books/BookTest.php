<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\User;

test('guests are redirected to the login page when visiting books', function () {
    $response = $this->get(route('books.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view the books index', function () {
    $user = User::factory()->create();
    Book::factory()->create(['title' => 'The Librarium Handbook']);

    $response = $this
        ->actingAs($user)
        ->get(route('books.index'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('books/Index')
            ->has('books.data', 1)
            ->where('books.data.0.title', 'The Librarium Handbook'));
});

test('books index searches the catalog by title and ISBN', function () {
    $user = User::factory()->create();
    Book::factory()->create([
        'title' => 'The 9781234567897 Handbook',
        'isbn' => '9780000000000',
    ]);
    Book::factory()->create([
        'title' => 'Another Handbook',
        'isbn' => '9781234567897',
    ]);
    Book::factory()->create([
        'title' => 'Unrelated Book',
        'isbn' => '9780000000001',
    ]);

    $response = $this->actingAs($user)->get(route('books.index', ['q' => '9781234567897']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('books.data', 2)
            ->where('filters.q', '9781234567897'));
});

test('authenticated users can create a book', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('books.store'), [
            'title' => 'The Librarium Handbook',
            'isbn' => '9781234567890',
            'publication_year' => 2026,
            'language' => 'en',
        ]);

    $book = Book::query()->first();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('books.show', $book));

    expect($book)->not->toBeNull();
    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'title' => 'The Librarium Handbook',
        'isbn' => '9781234567890',
    ]);
});

test('book creation requires a title', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('books.create'))
        ->post(route('books.store'), [
            'publication_year' => 2026,
        ]);

    $response
        ->assertSessionHasErrors('title')
        ->assertRedirect(route('books.create'));

    $this->assertDatabaseCount('books', 0);
});

test('authenticated users can assign relationships when creating a book', function () {
    $user = User::factory()->create();
    $author = Author::factory()->create();
    $category = Category::factory()->create();
    $collection = Collection::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('books.store'), [
            'title' => 'The Librarium Handbook',
            'author_ids' => [$author->id],
            'category_ids' => [$category->id],
            'collection_ids' => [$collection->id],
        ]);

    $book = Book::query()->first();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('books.show', $book));

    $book->load(['authors', 'categories', 'collections']);

    expect($book->authors->modelKeys())->toContain($author->id)
        ->and($book->categories->modelKeys())->toContain($category->id)
        ->and($book->collections->modelKeys())->toContain($collection->id);
});

test('book publication year cannot be in the future', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('books.create'))
        ->post(route('books.store'), [
            'title' => 'The Librarium Handbook',
            'publication_year' => now()->year + 1,
        ]);

    $response
        ->assertSessionHasErrors('publication_year')
        ->assertRedirect(route('books.create'));

    $this->assertDatabaseCount('books', 0);
});

test('authenticated users can update a book', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create(['title' => 'Draft title']);

    $response = $this
        ->actingAs($user)
        ->put(route('books.update', $book), [
            'title' => 'Published title',
            'language' => 'pt-BR',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('books.show', $book));

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'title' => 'Published title',
        'language' => 'pt-BR',
    ]);
});

test('authenticated users can delete a book', function () {
    $user = User::factory()->create();
    $book = Book::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('books.destroy', $book));

    $response->assertRedirect(route('books.index'));
    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

test('books index applies catalog health filters', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $makeBook = function (string $title, array $attributes = [], bool $classified = true) use ($category): Book {
        $book = Book::factory()->create(array_replace([
            'title' => $title,
            'isbn' => '9781234567890',
            'description' => 'A description.',
            'language' => 'en',
            'publication_year' => 2000,
            'cover_path' => 'covers/book.jpg',
            'cover_url' => null,
        ], $attributes));

        if ($classified) {
            $book->categories()->attach($category);
        }

        return $book;
    };

    $makeBook('Missing cover', ['cover_path' => null]);
    $makeBook('Unclassified', [], classified: false);
    $makeBook('Missing ISBN', ['isbn' => '']);
    $makeBook('Missing description', ['description' => '   ']);
    $makeBook('Unknown language', ['language' => null]);
    $makeBook('Blank language', ['language' => '   ']);
    $makeBook('Unknown year', ['publication_year' => null]);
    $makeBook('Empty cover URL', ['cover_path' => null, 'cover_url' => '']);
    $makeBook('Whitespace cover path', ['cover_path' => '   ']);
    $makeBook('Has remote cover', ['cover_path' => null, 'cover_url' => 'https://example.test/cover.jpg']);

    $this->actingAs($user);

    foreach ([
        ['missing_covers', 'Missing cover', 3],
        ['unclassified', 'Unclassified', 1],
        ['missing_isbn', 'Missing ISBN', 1],
        ['missing_descriptions', 'Missing description', 1],
        ['unknown_year', 'Unknown year', 1],
    ] as [$filter, $title, $count]) {
        $this->get(route('books.index', ['filter' => $filter]))
            ->assertOk()
            ->assertInertia(function ($page) use ($filter, $title, $count): void {
                $page->component('books/Index')
                    ->has('books.data', $count)
                    ->where('filters.filter', $filter);

                if ($count === 1) {
                    $page->where('books.data.0.title', $title);
                }
            });
    }

    $this->get(route('books.index', ['filter' => 'unknown_language']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('books.data', 2));
});

test('books index filters by category, language, and inclusive year range', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $makeBook = function (string $title, array $attributes = [], bool $categorized = false) use ($category): Book {
        $book = Book::factory()->create(array_replace([
            'title' => $title,
            'language' => 'en',
            'publication_year' => 1965,
        ], $attributes));

        if ($categorized) {
            $book->categories()->attach($category);
        }

        return $book;
    };

    $makeBook('Category match', categorized: true);
    $makeBook('Category mismatch', ['publication_year' => 1959]);
    $makeBook('Portuguese match', ['language' => 'pt-BR']);
    $makeBook('Range lower bound', ['publication_year' => 1960]);
    $makeBook('Range upper bound', ['publication_year' => 1969]);
    $makeBook('Outside year range', ['publication_year' => 1970]);

    $this->actingAs($user);

    $this->get(route('books.index', ['category' => $category->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Category match'));

    $this->get(route('books.index', ['language' => 'pt-BR']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('books.data', 1)
            ->where('books.data.0.title', 'Portuguese match'));

    $this->get(route('books.index', ['year_from' => 1960, 'year_to' => 1969]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('books.data', 4)
            ->where('filters.year_from', 1960)
            ->where('filters.year_to', 1969));
});
