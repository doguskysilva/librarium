<?php

use App\Models\Author;
use App\Models\User;

test('guests are redirected to the login page when visiting authors', function () {
    $response = $this->get(route('authors.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view the authors index', function () {
    $user = User::factory()->create();
    Author::factory()->create(['name' => 'Ursula K. Le Guin']);

    $response = $this
        ->actingAs($user)
        ->get(route('authors.index'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('authors/Index')
            ->has('authors.data', 1)
            ->where('authors.data.0.name', 'Ursula K. Le Guin'));
});

test('authenticated users can create an author', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('authors.store'), [
            'name' => 'Ursula K. Le Guin',
            'biography' => 'American author of speculative fiction.',
        ]);

    $author = Author::query()->first();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('authors.show', $author));

    expect($author)->not->toBeNull();
    $this->assertDatabaseHas('authors', [
        'id' => $author->id,
        'name' => 'Ursula K. Le Guin',
        'biography' => 'American author of speculative fiction.',
    ]);
});

test('author creation requires a name', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('authors.create'))
        ->post(route('authors.store'), [
            'biography' => 'A biography without a name.',
        ]);

    $response
        ->assertSessionHasErrors('name')
        ->assertRedirect(route('authors.create'));

    $this->assertDatabaseCount('authors', 0);
});

test('authenticated users can update an author', function () {
    $user = User::factory()->create();
    $author = Author::factory()->create(['name' => 'Original name']);

    $response = $this
        ->actingAs($user)
        ->put(route('authors.update', $author), [
            'name' => 'Updated name',
            'biography' => 'Updated biography.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('authors.show', $author));

    $this->assertDatabaseHas('authors', [
        'id' => $author->id,
        'name' => 'Updated name',
        'biography' => 'Updated biography.',
    ]);
});

test('authenticated users can delete an author', function () {
    $user = User::factory()->create();
    $author = Author::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('authors.destroy', $author));

    $response->assertRedirect(route('authors.index'));
    $this->assertDatabaseMissing('authors', ['id' => $author->id]);
});
