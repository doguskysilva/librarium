<?php

use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\User;

test('guests are redirected to the login page when visiting collections', function () {
    $response = $this->get(route('collections.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view the collections index', function () {
    $user = User::factory()->create();
    Collection::factory()->create([
        'name' => 'The Lord of the Rings',
        'type' => CollectionType::LiterarySeries,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('collections.index'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('collections/Index')
            ->has('collections.data', 1)
            ->where('collections.data.0.name', 'The Lord of the Rings')
            ->where('collections.data.0.type', CollectionType::LiterarySeries->value));
});

test('authenticated users can create a collection', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('collections.store'), [
            'name' => 'The Lord of the Rings',
            'type' => CollectionType::LiterarySeries->value,
            'description' => 'A narrative sequence.',
        ]);

    $collection = Collection::query()->first();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('collections.show', $collection));

    expect($collection)->not->toBeNull();
    $this->assertDatabaseHas('collections', [
        'id' => $collection->id,
        'name' => 'The Lord of the Rings',
        'type' => CollectionType::LiterarySeries->value,
    ]);
});

test('collection creation requires a supported type', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('collections.create'))
        ->post(route('collections.store'), [
            'name' => 'Invalid collection',
            'type' => 'unsupported',
        ]);

    $response
        ->assertSessionHasErrors('type')
        ->assertRedirect(route('collections.create'));

    $this->assertDatabaseCount('collections', 0);
});

test('authenticated users can update a collection', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->create([
        'name' => 'Original collection',
        'type' => CollectionType::Thematic,
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('collections.update', $collection), [
            'name' => 'Updated collection',
            'type' => CollectionType::Editorial->value,
            'description' => 'Updated description.',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('collections.show', $collection));

    $this->assertDatabaseHas('collections', [
        'id' => $collection->id,
        'name' => 'Updated collection',
        'type' => CollectionType::Editorial->value,
    ]);
});

test('authenticated users can delete a collection', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('collections.destroy', $collection));

    $response->assertRedirect(route('collections.index'));
    $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
});
