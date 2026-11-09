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
