<?php

namespace App\Actions\Inventory;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection as BookCollection;
use Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryImporter
{
    public function __construct(private InventoryDocumentValidator $validator) {}

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function run(array $document, bool $apply = false): array
    {
        $document = $this->validator->normalizeDocument($document);
        $errors = $this->validator->errors($document);

        if ($errors !== []) {
            return [
                'status' => 'invalid',
                'errors' => $errors,
            ];
        }

        if ($apply && ! $document['inventory']['is_complete']) {
            return [
                'status' => 'blocked',
                'errors' => [
                    'inventory.is_complete' => ['Set inventory.is_complete to true after verifying the physical inventory before applying it.'],
                ],
            ];
        }

        $report = $this->buildPlan($document);

        if (! $apply) {
            return $report;
        }

        return DB::transaction(function () use ($document): array {
            $report = $this->buildPlan($document);

            $this->persist($document, $report);

            $report['status'] = 'applied';
            $report['summary'] = $this->summarize($report);

            return $report;
        });
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function buildPlan(array $document): array
    {
        $entities = [
            'authors' => $this->planNamedEntities($document['authors'], 'authors', ['biography']),
            'categories' => $this->planNamedEntities($document['categories'], 'categories', ['description']),
            'collections' => $this->planNamedEntities($document['collections'], 'collections', ['description']),
        ];

        $books = [];

        foreach ($document['books'] as $index => $record) {
            $books[] = $this->planBook($record, $index, $document, $entities);
        }

        $relationships = $this->planCollectionMemberships($document, $entities, $books);

        return [
            'status' => 'dry_run',
            'inventory' => [
                'name' => $document['inventory']['name'],
                'schema_version' => $document['schema_version'],
                'is_complete' => $document['inventory']['is_complete'],
            ],
            'summary' => [
                'authors' => $this->summarizeRecords($entities['authors']),
                'categories' => $this->summarizeRecords($entities['categories']),
                'collections' => $this->summarizeRecords($entities['collections']),
                'books' => $this->summarizeRecords($books),
                'collection_memberships' => $this->summarizeRecords($relationships),
            ],
            'records' => [
                ...$entities,
                'books' => $books,
                'collection_memberships' => $relationships,
            ],
            'notes' => [
                'Only records marked verified are eligible to import.',
                'Existing populated fields and relationships are preserved; incoming nulls never erase catalog data.',
                'Field provenance remains in the source JSON artifact and is not copied into the application database.',
                'Author external identifiers remain in the source JSON; book external identifiers are stored in the database.',
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  'authors'|'categories'|'collections'  $entity
     * @param  list<string>  $fillableFields
     * @return list<array<string, mixed>>
     */
    private function planNamedEntities(array $records, string $entity, array $fillableFields): array
    {
        $existingRecordsByName = [];

        foreach ($this->allModels($entity) as $model) {
            $existingRecordsByName[$this->normalizeName((string) $model->getAttribute('name'))][] = $model;
        }

        $plans = [];
        $plannedCategorySlugs = [];

        foreach ($records as $index => $record) {
            $name = $record['name'];
            $normalizedName = $this->normalizeName($name);

            if ($record['review_status'] !== 'verified') {
                $plans[] = $this->recordPlan($name, 'skipped', $index, reason: 'Record is not verified.');

                continue;
            }

            $matches = $existingRecordsByName[$normalizedName] ?? [];

            if (count($matches) > 1) {
                $plans[] = $this->recordPlan($name, 'ambiguous', $index, reason: 'Multiple catalog records have this name.');

                continue;
            }

            $existing = $matches[0] ?? null;

            if ($existing instanceof BookCollection && $existing->getRawOriginal('type') !== $record['type']) {
                $plans[] = $this->recordPlan($name, 'conflict', $index, reason: 'Existing collection type differs from the inventory.');

                continue;
            }

            $attributes = $this->entityAttributes($record, $entity, $fillableFields);

            if ($entity === 'categories' && $existing === null) {
                if (isset($plannedCategorySlugs[$attributes['slug']])) {
                    $plans[] = $this->recordPlan($name, 'conflict', $index, reason: 'The category slug is already used by another category in this inventory.');

                    continue;
                }

                $slugOwner = Category::query()->where('slug', $attributes['slug'])->first();

                if ($slugOwner !== null && $this->normalizeName($slugOwner->name) !== $normalizedName) {
                    $plans[] = $this->recordPlan($name, 'conflict', $index, reason: 'The category slug is already used by a different catalog category.');

                    continue;
                }

                $plannedCategorySlugs[$attributes['slug']] = true;
            }

            $missingAttributes = $existing === null
                ? $attributes
                : $this->missingAttributes($existing, $attributes);

            $action = $existing === null
                ? 'create'
                : ($missingAttributes === [] ? 'unchanged' : 'update');

            $plans[] = [
                ...$this->recordPlan($name, $action, $index),
                'existing_id' => $existing?->getKey(),
                'attributes' => $missingAttributes,
            ];
        }

        return $plans;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  'authors'|'categories'|'collections'  $entity
     * @param  list<string>  $fillableFields
     * @return array<string, mixed>
     */
    private function entityAttributes(array $record, string $entity, array $fillableFields): array
    {
        $attributes = ['name' => $record['name']];

        foreach ($fillableFields as $field) {
            $attributes[$field] = $record[$field] ?? null;
        }

        if ($entity === 'categories') {
            $attributes['slug'] = $record['slug'] ?? Str::slug($record['name']);
        }

        if ($entity === 'collections') {
            $attributes['type'] = $record['type'];
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $document
     * @param  array<string, list<array<string, mixed>>>  $entities
     * @return array<string, mixed>
     */
    private function planBook(array $record, int $index, array $document, array $entities): array
    {
        if ($record['review_status'] !== 'verified') {
            return $this->recordPlan($record['source_key'], 'skipped', $index, reason: 'Book is not verified.');
        }

        if (! $this->isFieldVerified($record, 'title')) {
            return $this->recordPlan($record['source_key'], 'skipped', $index, reason: 'Title is not marked verified in metadata_provenance.');
        }

        $authorNames = $this->isFieldVerified($record, 'authors')
            ? array_column($record['authors'], 'name')
            : [];
        $categoryNames = $this->isFieldVerified($record, 'categories') ? $record['categories'] : [];
        $collectionReferences = $this->isFieldVerified($record, 'collections')
            ? $this->collectionReferences($record, $document)
            : [];

        foreach ($authorNames as $authorName) {
            if (! $this->entityCanResolve($entities['authors'], $authorName, 'authors')) {
                return $this->recordPlan($record['source_key'], 'skipped', $index, reason: "Author [$authorName] is not verified or has an ambiguous catalog match.");
            }
        }

        foreach ($categoryNames as $categoryName) {
            if (! $this->entityCanResolve($entities['categories'], $categoryName, 'categories')) {
                return $this->recordPlan($record['source_key'], 'skipped', $index, reason: "Category [$categoryName] is not verified or has an ambiguous catalog match.");
            }
        }

        foreach ($collectionReferences as $reference) {
            if (! $this->entityCanResolve($entities['collections'], $reference['name'], 'collections')) {
                return $this->recordPlan($record['source_key'], 'skipped', $index, reason: "Collection [{$reference['name']}] is not verified or has an ambiguous catalog match.");
            }
        }

        $candidate = Book::query()
            ->with(['authors', 'externalIdentifiers'])
            ->where('source_key', $record['source_key'])
            ->first();

        $matchedBy = $candidate === null ? null : 'source_key';
        $identifierCandidates = [];

        if ($candidate === null && $this->isFieldVerified($record, 'isbn') && $record['isbn'] !== null) {
            $identifierCandidates = Book::query()
                ->with(['authors', 'externalIdentifiers'])
                ->where('isbn', $record['isbn'])
                ->get()
                ->all();

            $identifierCandidates = array_values($identifierCandidates);
            $candidate = $this->uniqueEditionMatch($identifierCandidates, $record);
            $matchedBy = $candidate === null ? null : 'isbn';
        }

        if ($candidate === null && $identifierCandidates === [] && $this->isFieldVerified($record, 'identifiers')) {
            $candidate = $this->externalIdentifierMatch($record);
            $matchedBy = $candidate === null ? null : 'external_identifier';
        }

        if ($candidate === null && $identifierCandidates !== []) {
            return $this->recordPlan($record['source_key'], 'conflict', $index, reason: 'ISBN exists in the catalog but edition consistency could not be confirmed.');
        }

        if ($this->isFieldVerified($record, 'identifiers') && $this->hasExternalIdentifierConflict($record, $candidate)) {
            return $this->recordPlan($record['source_key'], 'conflict', $index, reason: 'An external identifier is already assigned to a different edition.');
        }

        if ($candidate !== null && $this->isFieldVerified($record, 'isbn') && $record['isbn'] !== null && $candidate->isbn !== null && $candidate->isbn !== $record['isbn']) {
            return $this->recordPlan($record['source_key'], 'conflict', $index, reason: 'The matched catalog record has a different ISBN.');
        }

        $attributes = $this->bookAttributes($record);
        $missingAttributes = $candidate === null
            ? $attributes
            : $this->missingAttributes($candidate, $attributes);

        if ($candidate !== null && $candidate->source_key === null) {
            $missingAttributes['source_key'] = $record['source_key'];
        }

        $action = $candidate === null
            ? 'create'
            : ($missingAttributes === [] ? 'unchanged' : 'update');

        return [
            ...$this->recordPlan($record['source_key'], $action, $index, matched_by: $matchedBy, preserved_fields: $candidate === null ? [] : $this->preservedFields($candidate, $attributes)),
            'existing_id' => $candidate?->getKey(),
            'attributes' => $missingAttributes,
            'author_names' => $authorNames,
            'category_names' => $categoryNames,
            'collection_references' => $collectionReferences,
            'identifiers' => $this->isFieldVerified($record, 'identifiers') ? $record['identifiers'] : [],
            'unverified_fields' => $this->unverifiedFields($record),
        ];
    }

    /**
     * @param  list<Book>  $candidates
     * @param  array<string, mixed>  $record
     */
    private function uniqueEditionMatch(array $candidates, array $record): ?Book
    {
        if (count($candidates) !== 1) {
            return null;
        }

        $candidate = $candidates[0];

        return $this->editionMatches($candidate, $record) ? $candidate : null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function externalIdentifierMatch(array $record): ?Book
    {
        $matches = [];

        foreach ($record['identifiers'] as $identifier) {
            $identifierMatches = Book::query()
                ->whereHas('externalIdentifiers', fn ($query) => $query
                    ->where('provider', $identifier['provider'])
                    ->where('type', $identifier['type'])
                    ->where('value', $identifier['value']))
                ->with(['authors', 'externalIdentifiers'])
                ->get();

            foreach ($identifierMatches as $book) {
                $matches[$book->getKey()] = $book;
            }
        }

        $matches = array_values($matches);

        if (count($matches) !== 1) {
            return null;
        }

        $candidate = $matches[0];

        return $this->editionMatches($candidate, $record) ? $candidate : null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function hasExternalIdentifierConflict(array $record, ?Book $candidate): bool
    {
        foreach ($record['identifiers'] as $identifier) {
            $ownerId = DB::table('book_external_identifiers')
                ->where('provider', $identifier['provider'])
                ->where('type', $identifier['type'])
                ->where('value', $identifier['value'])
                ->value('book_id');

            if ($ownerId !== null && ($candidate === null || (string) $ownerId !== (string) $candidate->getKey())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function editionMatches(Book $candidate, array $record): bool
    {
        if ($this->normalizeName($candidate->title) !== $this->normalizeName($record['title'])) {
            return false;
        }

        $existingAuthors = $candidate->authors
            ->map(fn (Author $author): string => $this->normalizeName($author->name))
            ->sort()
            ->values()
            ->all();

        if (! $this->isFieldVerified($record, 'authors')) {
            return false;
        }

        $incomingAuthors = [];

        foreach ($record['authors'] as $author) {
            $incomingAuthors[] = $this->normalizeName($author['name']);
        }

        sort($incomingAuthors);

        return $existingAuthors === $incomingAuthors;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function bookAttributes(array $record): array
    {
        $attributes = ['source_key' => $record['source_key']];

        foreach (['title', 'subtitle', 'translator', 'isbn', 'publisher', 'edition', 'publication_year', 'language', 'description', 'cover_url'] as $field) {
            if ($this->isFieldVerified($record, $field)) {
                $attributes[$field] = $record[$field] ?? null;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $document
     * @param  array{authors: list<array<string, mixed>>, categories: list<array<string, mixed>>, collections: list<array<string, mixed>>}  $entities
     * @param  list<array<string, mixed>>  $bookPlans
     * @return list<array<string, mixed>>
     */
    private function planCollectionMemberships(array $document, array $entities, array $bookPlans): array
    {
        $memberships = [];
        $collectionsByName = [];

        foreach ($document['collections'] as $collection) {
            $collectionsByName[$this->normalizeName($collection['name'])] = $collection;
        }

        $booksByKey = [];

        foreach ($bookPlans as $bookPlan) {
            $booksByKey[$bookPlan['key']] = $bookPlan;
        }

        foreach ($document['books'] as $book) {
            if (! $this->isFieldVerified($book, 'collections')) {
                foreach ($this->collectionReferences($book, $document) as $reference) {
                    $membershipKey = $book['source_key'].'|'.$this->normalizeName($reference['name']);
                    $memberships[$membershipKey] = [
                        'source_key' => $book['source_key'],
                        'collection' => $reference['name'],
                        'position' => $reference['position'],
                        'action' => 'skipped',
                        'reason' => 'Collection membership is not marked verified.',
                    ];
                }

                continue;
            }

            foreach ($this->collectionReferences($book, $document) as $reference) {
                $collection = $collectionsByName[$this->normalizeName($reference['name'])];
                $membershipKey = $book['source_key'].'|'.$this->normalizeName($reference['name']);

                if (isset($memberships[$membershipKey])) {
                    continue;
                }

                $bookPlan = $booksByKey[$book['source_key']] ?? null;
                $collectionPlan = null;

                foreach ($entities['collections'] as $plan) {
                    if ($this->normalizeName($plan['key']) === $this->normalizeName($reference['name'])) {
                        $collectionPlan = $plan;

                        break;
                    }
                }

                $action = 'additive';
                $reason = null;
                $existingBookId = $bookPlan['existing_id'] ?? null;
                $existingCollection = $this->matchingModels('collections', $reference['name'])[0] ?? null;

                if (! in_array($bookPlan['action'] ?? 'skipped', ['create', 'update', 'unchanged'], true)
                    || ! in_array($collectionPlan['action'] ?? 'skipped', ['create', 'update', 'unchanged'], true)) {
                    $action = 'skipped';
                    $reason = 'Book or collection is not eligible for import.';
                } elseif ($existingBookId !== null && $existingCollection !== null && DB::table('collection_book')
                    ->where('book_id', $existingBookId)
                    ->where('collection_id', $existingCollection->getKey())
                    ->exists()) {
                    $action = 'unchanged';
                } elseif ($reference['position'] !== null && $existingCollection !== null && DB::table('collection_book')
                    ->where('collection_id', $existingCollection->getKey())
                    ->where('position', $reference['position'])
                    ->exists()) {
                    $action = 'conflict';
                    $reason = 'Collection position is already assigned to another book.';
                }

                $memberships[$membershipKey] = [
                    'source_key' => $book['source_key'],
                    'collection' => $collection['name'],
                    'position' => $reference['position'],
                    'action' => $action,
                    ...($reason === null ? [] : ['reason' => $reason]),
                ];
            }
        }

        return array_values($memberships);
    }

    /**
     * @param  array<string, mixed>  $book
     * @param  array<string, mixed>  $document
     * @return list<array{name: string, type: string, position: ?int}>
     */
    private function collectionReferences(array $book, array $document): array
    {
        $references = [];

        foreach ($book['collections'] as $collection) {
            $references[$this->normalizeName($collection['name'])] = $collection;
        }

        foreach ($document['collections'] as $collection) {
            foreach ($collection['members'] as $member) {
                if ($member['book_source_key'] !== $book['source_key']) {
                    continue;
                }

                $key = $this->normalizeName($collection['name']);
                $references[$key] ??= [
                    'name' => $collection['name'],
                    'type' => $collection['type'],
                    'position' => $member['position'],
                ];
            }
        }

        return array_values($references);
    }

    /**
     * @param  list<array<string, mixed>>  $plans
     * @param  'authors'|'categories'|'collections'  $entity
     */
    private function entityCanResolve(array $plans, string $name, string $entity): bool
    {
        $matchingPlan = null;

        foreach ($plans as $plan) {
            if ($this->normalizeName($plan['key']) === $this->normalizeName($name)) {
                $matchingPlan = $plan;

                break;
            }
        }

        if ($matchingPlan === null) {
            return count($this->matchingModels($entity, $name)) === 1;
        }

        return in_array($matchingPlan['action'], ['create', 'update', 'unchanged'], true);
    }

    /**
     * @param  'authors'|'categories'|'collections'  $entity
     * @return list<Model>
     */
    private function matchingModels(string $entity, string $name): array
    {
        $matches = [];

        foreach ($this->allModels($entity) as $model) {
            if ($this->normalizeName((string) $model->getAttribute('name')) === $this->normalizeName($name)) {
                $matches[] = $model;
            }
        }

        return $matches;
    }

    /**
     * @param  'authors'|'categories'|'collections'  $entity
     * @return Generator<int, Model, mixed, void>
     */
    private function allModels(string $entity): Generator
    {
        if ($entity === 'authors') {
            foreach (Author::query()->get() as $author) {
                yield $author;
            }

            return;
        }

        if ($entity === 'categories') {
            foreach (Category::query()->get() as $category) {
                yield $category;
            }

            return;
        }

        foreach (BookCollection::query()->get() as $collection) {
            yield $collection;
        }
    }

    /**
     * @param  'authors'|'categories'|'collections'  $entity
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $plan
     * @param  list<string>  $fillableFields
     */
    private function persistNamedEntity(string $entity, array $record, array $plan, array $fillableFields): Model
    {
        $model = match ($entity) {
            'authors' => isset($plan['existing_id'])
                ? Author::query()->findOrFail((string) $plan['existing_id'])
                : Author::query()->create($this->entityAttributes($record, $entity, $fillableFields)),
            'categories' => isset($plan['existing_id'])
                ? Category::query()->findOrFail((string) $plan['existing_id'])
                : Category::query()->create($this->entityAttributes($record, $entity, $fillableFields)),
            'collections' => isset($plan['existing_id'])
                ? BookCollection::query()->findOrFail((string) $plan['existing_id'])
                : BookCollection::query()->create($this->entityAttributes($record, $entity, $fillableFields)),
        };

        if ($plan['attributes'] !== []) {
            $model->fill($plan['attributes'])->save();
        }

        return $model;
    }

    /**
     * @param  'authors'|'categories'|'collections'  $entity
     * @param  list<array<string, mixed>>  $plans
     * @param  array<string, mixed>  $document
     * @param  list<string>  $fillableFields
     * @return array<string, Model>
     */
    private function persistNamedEntities(string $entity, array $plans, array $document, array $fillableFields): array
    {
        $resolved = [];

        foreach ($plans as $plan) {
            if (! in_array($plan['action'], ['create', 'update', 'unchanged'], true)) {
                continue;
            }

            $record = $document[$entity][$plan['index']];
            $model = $this->persistNamedEntity($entity, $record, $plan, $fillableFields);
            $resolved[$this->normalizeName($record['name'])] = $model;
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $preserved_fields
     * @return array<string, mixed>
     */
    private function recordPlan(string $key, string $action, int $index, ?string $reason = null, ?string $matched_by = null, array $preserved_fields = []): array
    {
        return array_filter([
            'key' => $key,
            'index' => $index,
            'action' => $action,
            'reason' => $reason,
            'matched_by' => $matched_by,
            'preserved_fields' => $preserved_fields,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function missingAttributes(Model $model, array $attributes): array
    {
        return array_filter(
            $attributes,
            fn (mixed $value, string $key): bool => $model->getAttribute($key) === null && $value !== null,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function preservedFields(Model $model, array $attributes): array
    {
        $fields = [];

        foreach ($attributes as $key => $value) {
            $existingValue = $model->getAttribute($key);

            if ($existingValue === null) {
                continue;
            }

            if ($value === null || (string) $existingValue !== (string) $value) {
                $fields[] = $key;
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $report
     */
    private function persist(array $document, array $report): void
    {
        $resolved = [
            'authors' => $this->persistNamedEntities('authors', $report['records']['authors'], $document, ['biography']),
            'categories' => $this->persistNamedEntities('categories', $report['records']['categories'], $document, ['description']),
            'collections' => $this->persistNamedEntities('collections', $report['records']['collections'], $document, ['description']),
            'books' => [],
        ];

        foreach ($report['records']['books'] as $plan) {
            if (! in_array($plan['action'], ['create', 'update', 'unchanged'], true)) {
                continue;
            }

            $record = $document['books'][$plan['index']];
            $book = isset($plan['existing_id'])
                ? Book::query()->findOrFail((string) $plan['existing_id'])
                : Book::query()->create($plan['attributes']);

            if (isset($plan['existing_id']) && $plan['attributes'] !== []) {
                $book->fill($plan['attributes'])->save();
            }

            $resolved['books'][$record['source_key']] = $book;

            foreach ($plan['author_names'] as $name) {
                $author = $resolved['authors'][$this->normalizeName($name)] ?? $this->matchingModels('authors', $name)[0] ?? null;

                if ($author !== null) {
                    $book->authors()->syncWithoutDetaching([$author->getKey()]);
                }
            }

            foreach ($plan['category_names'] as $name) {
                $category = $resolved['categories'][$this->normalizeName($name)] ?? $this->matchingModels('categories', $name)[0] ?? null;

                if ($category !== null) {
                    $book->categories()->syncWithoutDetaching([$category->getKey()]);
                }
            }

            foreach ($plan['identifiers'] as $identifier) {
                $book->externalIdentifiers()->firstOrCreate(
                    [
                        'provider' => $identifier['provider'],
                        'type' => $identifier['type'],
                        'value' => $identifier['value'],
                    ],
                    ['url' => $identifier['url'] ?? null],
                );
            }
        }

        foreach ($report['records']['collection_memberships'] as $membership) {
            if ($membership['action'] !== 'additive') {
                continue;
            }

            $book = $resolved['books'][$membership['source_key']] ?? null;
            $collection = $resolved['collections'][$this->normalizeName($membership['collection'])] ?? $this->matchingModels('collections', $membership['collection'])[0] ?? null;

            if ($book === null || $collection === null) {
                continue;
            }

            $alreadyAttached = DB::table('collection_book')
                ->where('book_id', $book->getKey())
                ->where('collection_id', $collection->getKey())
                ->exists();

            if ($alreadyAttached) {
                continue;
            }

            $position = $membership['position'];
            $positionInUse = $position !== null && DB::table('collection_book')
                ->where('collection_id', $collection->getKey())
                ->where('position', $position)
                ->exists();

            $book->collections()->syncWithoutDetaching([
                $collection->getKey() => ['position' => $positionInUse ? null : $position],
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, int>
     */
    private function summarizeRecords(array $records): array
    {
        $summary = array_fill_keys(['create', 'update', 'unchanged', 'skipped', 'ambiguous', 'conflict', 'additive'], 0);

        foreach ($records as $record) {
            $summary[$record['action']] = ($summary[$record['action']] ?? 0) + 1;
        }

        return array_filter($summary, fn (int $count): bool => $count > 0);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, array<string, int>>
     */
    private function summarize(array $report): array
    {
        return [
            'authors' => $this->summarizeRecords($report['records']['authors']),
            'categories' => $this->summarizeRecords($report['records']['categories']),
            'collections' => $this->summarizeRecords($report['records']['collections']),
            'books' => $this->summarizeRecords($report['records']['books']),
            'collection_memberships' => $this->summarizeRecords($report['records']['collection_memberships']),
        ];
    }

    private function normalizeName(string $name): string
    {
        $lowercaseName = Str::lower(trim($name));

        return preg_replace('/\s+/u', ' ', $lowercaseName) ?? $lowercaseName;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function isFieldVerified(array $record, string $field): bool
    {
        $verifiedFields = $record['metadata_provenance']['verified_fields'] ?? null;

        return ! is_array($verifiedFields) || in_array($field, $verifiedFields, true);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return list<string>
     */
    private function unverifiedFields(array $record): array
    {
        $verifiedFields = $record['metadata_provenance']['verified_fields'] ?? null;

        if (! is_array($verifiedFields)) {
            return [];
        }

        return array_values(array_diff(
            ['subtitle', 'translator', 'authors', 'isbn', 'publisher', 'edition', 'publication_year', 'language', 'description', 'cover_url', 'categories', 'collections', 'identifiers'],
            $verifiedFields,
        ));
    }
}
