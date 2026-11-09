<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $author = Author::factory()->create();
    $collection = Collection::factory()->create();
    $computing = Category::factory()->create(['name' => 'Computing']);
    $dataStructures = Category::factory()->create(['name' => 'Data Structures']);
    Category::factory()->create(['name' => 'Unused']);

    $firstBook = Book::factory()->create([
        'title' => 'Algorithms',
        'language' => 'en',
        'publication_year' => 1959,
        'isbn' => null,
        'description' => '   ',
        'cover_path' => null,
        'cover_url' => null,
    ]);
    $firstBook->authors()->attach($author);
    $firstBook->categories()->attach([$computing->id, $dataStructures->id]);
    $firstBook->collections()->attach($collection);

    $secondBook = Book::factory()->create([
        'title' => 'Systems',
        'language' => 'en',
        'publication_year' => 1960,
        'isbn' => '9781234567890',
        'description' => 'A description.',
        'cover_path' => 'covers/systems.jpg',
        'cover_url' => null,
    ]);
    $secondBook->categories()->attach($computing);

    Book::factory()->create([
        'title' => 'Unknown metadata',
        'language' => ' ',
        'publication_year' => null,
        'isbn' => '',
        'description' => null,
        'cover_path' => ' ',
        'cover_url' => '',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('summary.books', 3)
            ->where('summary.authors', 1)
            ->where('summary.collections', 1)
            ->has('composition.booksByCategory', 2)
            ->where('composition.booksByCategory.0.name', 'Computing')
            ->where('composition.booksByCategory.0.count', 2)
            ->where('composition.booksByCategory.0.percentage', 66.67)
            ->where('composition.booksByCategory.1.count', 1)
            ->where('composition.booksByCategory.1.percentage', 33.33)
            ->where('composition.booksByLanguage.0.language', 'en')
            ->where('composition.booksByLanguage.0.count', 2)
            ->where('composition.booksByLanguage.1.language', null)
            ->where('composition.booksByLanguage.1.label', 'Unknown')
            ->where('composition.booksByLanguage.1.count', 1)
            ->where('catalogHealth.missingCovers', 2)
            ->where('catalogHealth.unclassified', 1)
            ->where('catalogHealth.missingIsbn', 2)
            ->where('catalogHealth.missingDescriptions', 2)
            ->where('publicationTimeline.0.decadeStart', 1950)
            ->where('publicationTimeline.0.count', 1)
            ->where('publicationTimeline.1.decadeStart', 1960)
            ->where('publicationTimeline.1.count', 1)
            ->where('publicationTimeline.2.label', 'Unknown year')
            ->where('publicationTimeline.2.decadeStart', null)
            ->where('publicationTimeline.2.count', 1));
});

test('the dashboard returns empty catalog metrics without dividing by zero', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('summary.books', 0)
            ->where('summary.authors', 0)
            ->where('summary.collections', 0)
            ->has('composition.booksByCategory', 0)
            ->has('composition.booksByLanguage', 0)
            ->where('catalogHealth.missingCovers', 0)
            ->where('catalogHealth.unclassified', 0)
            ->where('catalogHealth.missingIsbn', 0)
            ->where('catalogHealth.missingDescriptions', 0)
            ->has('publicationTimeline', 0));
});

test('the dashboard groups less frequent languages without losing their counts', function () {
    foreach (['en', 'pt', 'es', 'fr', 'de', 'it', 'nl', 'sv'] as $language) {
        Book::factory()->create(['language' => $language]);
    }

    Book::factory()->create(['language' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('composition.booksByLanguage', 8)
            ->where('composition.booksByLanguage.7.label', 'Other')
            ->where('composition.booksByLanguage.7.count', 2)
            ->has('composition.booksByLanguage.7.members', 2));
});

test('the category chart shows only the ten most-used categories', function () {
    $categories = collect(range(1, 11))
        ->map(fn (int $number) => Category::factory()->create([
            'name' => sprintf('Category %02d', $number),
        ]));

    foreach ($categories as $category) {
        Book::factory()->create()->categories()->attach($category);
    }

    Book::factory()->create()->categories()->attach($categories[10]);
    Category::factory()->create(['name' => 'Unused category']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('composition.booksByCategory', 10)
            ->where('composition.booksByCategory.0.name', 'Category 11')
            ->where('composition.booksByCategory.0.count', 2)
            ->where('composition.booksByCategory.9.name', 'Category 09'));
});
