<?php

use App\Actions\Inventory\InventoryImporter;
use App\Models\Author;
use App\Models\Book;
use App\Models\Collection as BookCollection;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function inventoryDocument(): array
{
    return [
        'schema' => 'librarium.inventory',
        'schema_version' => '1.0.0',
        'inventory' => [
            'name' => 'Test inventory',
            'language' => 'en',
            'created_at' => '2026-01-01',
            'source' => 'Test fixture',
            'is_complete' => true,
            'notes' => [],
        ],
        'authors' => [
            [
                'name' => 'Ada Lovelace',
                'biography' => null,
                'external_identifiers' => [],
                'review_status' => 'verified',
            ],
        ],
        'categories' => [
            [
                'name' => 'Computing',
                'description' => null,
                'review_status' => 'verified',
            ],
        ],
        'collections' => [
            [
                'name' => 'Analytical Engines',
                'type' => 'personal',
                'description' => null,
                'members' => [
                    ['book_source_key' => 'inventory-001', 'position' => 1],
                ],
                'review_status' => 'verified',
            ],
        ],
        'books' => [
            [
                'source_key' => 'inventory-001',
                'title' => 'Notes on the Analytical Engine',
                'subtitle' => null,
                'authors' => [['name' => 'Ada Lovelace']],
                'isbn' => '9780000000001',
                'publisher' => null,
                'edition' => null,
                'publication_year' => 1843,
                'language' => 'en',
                'description' => null,
                'cover_url' => null,
                'categories' => ['Computing'],
                'collections' => [
                    ['name' => 'Analytical Engines', 'type' => 'personal', 'position' => 1],
                ],
                'identifiers' => [],
                'metadata_provenance' => [],
                'review_status' => 'verified',
                'enrichment_status' => 'enriched',
            ],
        ],
    ];
}

test('a dry run validates and reports changes without writing catalog data', function () {
    $report = app(InventoryImporter::class)->run(inventoryDocument());

    expect($report['status'])->toBe('dry_run')
        ->and($report['summary']['books']['create'])->toBe(1)
        ->and($report['summary']['authors']['create'])->toBe(1);

    $this->assertDatabaseCount('books', 0);
    $this->assertDatabaseCount('authors', 0);
});

test('an inventory can be applied repeatedly without creating duplicates', function () {
    $importer = app(InventoryImporter::class);
    $document = inventoryDocument();

    $firstReport = $importer->run($document, apply: true);
    $secondReport = $importer->run($document, apply: true);

    expect($firstReport['status'])->toBe('applied')
        ->and($firstReport['summary']['books']['create'])->toBe(1)
        ->and($secondReport['summary']['books']['unchanged'])->toBe(1);

    $this->assertDatabaseCount('books', 1);
    $this->assertDatabaseCount('authors', 1);
    $this->assertDatabaseCount('categories', 1);
    $this->assertDatabaseCount('collections', 1);
    $this->assertDatabaseHas('books', [
        'source_key' => 'inventory-001',
        'title' => 'Notes on the Analytical Engine',
        'cover_url' => null,
    ]);

    $book = Book::query()->where('source_key', 'inventory-001')->firstOrFail();
    expect($book->authors)->toHaveCount(1)
        ->and($book->categories)->toHaveCount(1)
        ->and($book->collections)->toHaveCount(1);
});

test('invalid references prevent every database write', function () {
    $document = inventoryDocument();
    $document['books'][0]['authors'][0]['name'] = 'Unknown Author';

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['status'])->toBe('invalid')
        ->and($report['errors'])->toHaveKey('books.0.authors.0.name');

    $this->assertDatabaseCount('books', 0);
    $this->assertDatabaseCount('authors', 0);
});

test('an incomplete inventory cannot be applied', function () {
    $document = inventoryDocument();
    $document['inventory']['is_complete'] = false;

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['status'])->toBe('blocked')
        ->and($report['errors'])->toHaveKey('inventory.is_complete');

    $this->assertDatabaseCount('books', 0);
});

test('existing populated values and relationships are preserved while missing values are filled', function () {
    $author = Author::factory()->create(['name' => 'Ada Lovelace', 'biography' => 'Verified catalog biography.']);
    $book = Book::factory()->create([
        'source_key' => 'inventory-001',
        'title' => 'Catalog title',
        'isbn' => null,
        'description' => 'Catalog description.',
    ]);
    $book->authors()->attach($author);

    $document = inventoryDocument();
    $report = app(InventoryImporter::class)->run($document, apply: true);
    $book->refresh();

    expect($report['records']['books'][0]['preserved_fields'])->toContain('title', 'description')
        ->and($book->title)->toBe('Catalog title')
        ->and($book->description)->toBe('Catalog description.')
        ->and($book->isbn)->toBe('9780000000001')
        ->and($book->authors)->toHaveCount(1);
});

test('an ISBN match with inconsistent edition details is reported instead of merged', function () {
    $existingAuthor = Author::factory()->create(['name' => 'Different Author']);
    $existingBook = Book::factory()->create([
        'title' => 'Different title',
        'isbn' => '9780000000001',
    ]);
    $existingBook->authors()->attach($existingAuthor);

    $report = app(InventoryImporter::class)->run(inventoryDocument());

    expect($report['records']['books'][0]['action'])->toBe('conflict')
        ->and($report['records']['books'][0]['reason'])->toContain('edition consistency');

    $this->assertDatabaseCount('books', 1);
});

test('a provider identifier can match an existing edition after source key and ISBN matching', function () {
    $author = Author::factory()->create(['name' => 'Ada Lovelace']);
    $existingBook = Book::factory()->create([
        'title' => 'Notes on the Analytical Engine',
        'isbn' => null,
        'source_key' => null,
    ]);
    $existingBook->authors()->attach($author);
    $existingBook->externalIdentifiers()->create([
        'provider' => 'open_library',
        'type' => 'edition',
        'value' => 'OL123M',
    ]);

    $document = inventoryDocument();
    $document['books'][0]['identifiers'] = [
        ['provider' => 'open_library', 'type' => 'edition', 'value' => 'OL123M'],
    ];

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['records']['books'][0]['matched_by'])->toBe('external_identifier');

    $this->assertDatabaseCount('books', 1);
    $this->assertDatabaseHas('books', [
        'id' => $existingBook->id,
        'source_key' => 'inventory-001',
        'isbn' => '9780000000001',
    ]);
});

test('a collection membership position conflict is reported and not attached', function () {
    $collection = BookCollection::factory()->create([
        'name' => 'Analytical Engines',
        'type' => 'personal',
    ]);
    $existingBook = Book::factory()->create();
    $collection->books()->attach($existingBook, ['position' => 1]);

    $report = app(InventoryImporter::class)->run(inventoryDocument(), apply: true);

    expect($report['records']['collection_memberships'][0]['action'])->toBe('conflict');

    $importedBook = Book::query()->where('source_key', 'inventory-001')->firstOrFail();
    expect($importedBook->collections)->toBeEmpty();
});

test('unverified records are skipped during application', function () {
    $document = inventoryDocument();
    $document['books'][0]['review_status'] = 'needs_review';

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['records']['books'][0]['action'])->toBe('skipped');

    $this->assertDatabaseCount('books', 0);
});

test('proposed metadata fields are excluded even when the book record is verified', function () {
    $document = inventoryDocument();
    $document['books'][0]['metadata_provenance'] = [
        'verified_fields' => ['title', 'authors'],
        'proposed_fields' => ['isbn'],
    ];

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['records']['books'][0]['unverified_fields'])->toContain('isbn');

    $this->assertDatabaseHas('books', [
        'source_key' => 'inventory-001',
        'title' => 'Notes on the Analytical Engine',
        'isbn' => null,
    ]);
});

test('verified translator fields and box set collections are imported', function () {
    $document = inventoryDocument();
    $document['collections'][0]['type'] = 'box_set';
    $document['books'][0]['collections'][0]['type'] = 'box_set';
    $document['books'][0]['translator'] = 'Example Translator';
    $document['books'][0]['metadata_provenance'] = [
        'verified_fields' => ['title', 'authors', 'translator'],
    ];

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['status'])->toBe('applied');

    $this->assertDatabaseHas('books', [
        'source_key' => 'inventory-001',
        'translator' => 'Example Translator',
    ]);
    $this->assertDatabaseHas('collections', [
        'name' => 'Analytical Engines',
        'type' => 'box_set',
    ]);
});

test('unknown JSON properties are rejected before writes', function () {
    $document = inventoryDocument();
    $document['unexpected'] = 'not part of inventory schema';

    $report = app(InventoryImporter::class)->run($document, apply: true);

    expect($report['status'])->toBe('invalid')
        ->and($report['errors'])->toHaveKey('unexpected');

    $this->assertDatabaseCount('books', 0);
});

test('the artisan command writes a dry-run report without applying records', function () {
    $inventoryPath = tempnam(sys_get_temp_dir(), 'librarium-inventory-');
    $reportPath = tempnam(sys_get_temp_dir(), 'librarium-report-');

    file_put_contents($inventoryPath, json_encode(inventoryDocument(), JSON_THROW_ON_ERROR));

    try {
        $this->artisan('inventory:import', [
            'file' => $inventoryPath,
            '--report' => $reportPath,
        ])->assertSuccessful();

        $report = json_decode(file_get_contents($reportPath), true, flags: JSON_THROW_ON_ERROR);

        expect($report['status'])->toBe('dry_run')
            ->and($report['summary']['books']['create'])->toBe(1);

        $this->assertDatabaseCount('books', 0);
    } finally {
        unlink($inventoryPath);
        unlink($reportPath);
    }
});

test('guests are redirected away from the inventory import page', function () {
    $this->get(route('inventory-import.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can open the inventory import page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('inventory-import.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('inventory/Import'));
});

test('guests cannot submit an inventory import', function () {
    $this->post(route('inventory-import.preview'), [
        'file' => UploadedFile::fake()->createWithContent(
            'inventory.json',
            json_encode(inventoryDocument(), JSON_THROW_ON_ERROR),
        ),
    ])
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('books', 0);
});

test('the inventory import preview accepts JSON and makes no catalog changes', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('inventory-import.preview'), [
        'file' => UploadedFile::fake()->createWithContent(
            'inventory.json',
            json_encode(inventoryDocument(), JSON_THROW_ON_ERROR),
        ),
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'dry_run')
        ->assertJsonPath('records.authors.0.action', 'create')
        ->assertJsonPath('records.categories.0.action', 'create')
        ->assertJsonPath('records.collections.0.action', 'create')
        ->assertJsonPath('records.books.0.action', 'create')
        ->assertJsonPath('records.collection_memberships.0.action', 'additive');
    $this->assertDatabaseCount('books', 0);
    $this->assertDatabaseCount('authors', 0);
});

test('the inventory import apply endpoint persists a complete verified inventory', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('inventory-import.apply'), [
        'file' => UploadedFile::fake()->createWithContent(
            'inventory.json',
            json_encode(inventoryDocument(), JSON_THROW_ON_ERROR),
        ),
    ]);

    $response->assertOk()->assertJsonPath('status', 'applied');
    $this->assertDatabaseHas('books', [
        'source_key' => 'inventory-001',
        'title' => 'Notes on the Analytical Engine',
    ]);
});

test('the inventory import upload rejects malformed JSON', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('inventory-import.preview'), [
        'file' => UploadedFile::fake()->createWithContent('inventory.json', '{invalid'),
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.file.0', 'The selected file could not be parsed as JSON.');

    $this->assertDatabaseCount('books', 0);
});

test('the inventory import preview ties validation errors to their book identity', function () {
    $this->actingAs(User::factory()->create());
    $document = inventoryDocument();
    $document['books'][0]['title'] = '';

    $this->post(route('inventory-import.preview'), [
        'file' => UploadedFile::fake()->createWithContent(
            'inventory.json',
            json_encode($document, JSON_THROW_ON_ERROR),
        ),
    ])
        ->assertOk()
        ->assertJsonPath('status', 'invalid')
        ->assertJsonPath('record_labels.books.0', 'inventory-001')
        ->assertJsonFragment([
            'books.0.title' => ['The books.0.title field is required.'],
        ]);

    $this->assertDatabaseCount('books', 0);
});
