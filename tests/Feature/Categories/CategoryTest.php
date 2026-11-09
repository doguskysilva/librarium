<?php

use App\Models\Category;
use App\Models\User;

test('guests are redirected to the login page when visiting categories', function () {
    $response = $this->get(route('categories.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view the categories index', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Science Fiction', 'slug' => 'science-fiction']);

    $response = $this
        ->actingAs($user)
        ->get(route('categories.index'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.slug', 'science-fiction'));
});

test('authenticated users can create a category', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Science Fiction',
            'slug' => 'science-fiction',
            'description' => 'Speculative stories and imagined futures.',
        ]);

    $category = Category::query()->first();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.show', $category));

    expect($category)->not->toBeNull();
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Science Fiction',
        'slug' => 'science-fiction',
    ]);
});

test('category creation requires a unique slug', function () {
    $user = User::factory()->create();
    Category::factory()->create(['slug' => 'science-fiction']);

    $response = $this
        ->actingAs($user)
        ->from(route('categories.create'))
        ->post(route('categories.store'), [
            'name' => 'Another category',
            'slug' => 'science-fiction',
        ]);

    $response
        ->assertSessionHasErrors('slug')
        ->assertRedirect(route('categories.create'));

    $this->assertDatabaseCount('categories', 1);
});

test('authenticated users can update a category without changing its slug', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'name' => 'Science Fiction',
        'slug' => 'science-fiction',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('categories.update', $category), [
            'name' => 'Speculative Fiction',
            'slug' => 'science-fiction',
            'description' => 'Updated description.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.show', $category));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Speculative Fiction',
        'slug' => 'science-fiction',
    ]);
});

test('category updates reject a slug used by another category', function () {
    $user = User::factory()->create();
    $existingCategory = Category::factory()->create(['slug' => 'science-fiction']);
    $category = Category::factory()->create(['slug' => 'history']);

    $response = $this
        ->actingAs($user)
        ->from(route('categories.edit', $category))
        ->put(route('categories.update', $category), [
            'name' => $category->name,
            'slug' => $existingCategory->slug,
        ]);

    $response
        ->assertSessionHasErrors('slug')
        ->assertRedirect(route('categories.edit', $category));

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'slug' => 'history',
    ]);
});

test('authenticated users can delete a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('categories.destroy', $category));

    $response->assertRedirect(route('categories.index'));
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
