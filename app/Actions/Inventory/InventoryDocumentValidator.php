<?php

namespace App\Actions\Inventory;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InventoryDocumentValidator
{
    /**
     * @param  array<string, mixed>  $document
     * @return array<string, list<string>>
     */
    public function errors(array $document): array
    {
        $document = $this->normalizeDocument($document);
        $errors = [];
        $unexpectedProperties = array_diff(array_keys($document), [
            'schema',
            'schema_version',
            'inventory',
            'authors',
            'categories',
            'collections',
            'books',
        ]);

        foreach ($unexpectedProperties as $property) {
            $errors[$property][] = 'Unexpected property.';
        }

        $validation = Validator::make($document, [
            'schema' => ['required', 'in:librarium.inventory'],
            'schema_version' => ['required', 'in:1.0.0'],
            'inventory' => ['required', 'array:name,language,created_at,source,is_complete,notes'],
            'inventory.name' => ['required', 'string', 'min:1'],
            'inventory.language' => ['required', 'string', 'min:2'],
            'inventory.created_at' => ['required', 'date_format:Y-m-d'],
            'inventory.source' => ['required', 'string', 'min:1'],
            'inventory.is_complete' => ['required', 'boolean'],
            'inventory.notes' => ['present', 'array'],
            'inventory.notes.*' => ['string'],
            'authors' => ['present', 'array'],
            'authors.*' => ['required', 'array:name,biography,external_identifiers,review_status'],
            'authors.*.name' => ['required', 'string', 'min:1'],
            'authors.*.biography' => ['present', 'nullable', 'string'],
            'authors.*.external_identifiers' => ['present', 'array'],
            'authors.*.external_identifiers.*' => ['required', 'array:provider,type,value,url'],
            'authors.*.external_identifiers.*.provider' => ['required', 'string', 'min:1'],
            'authors.*.external_identifiers.*.type' => ['required', 'string', 'min:1'],
            'authors.*.external_identifiers.*.value' => ['required', 'string', 'min:1'],
            'authors.*.external_identifiers.*.url' => ['sometimes', 'nullable', 'url'],
            'authors.*.review_status' => ['required', 'in:needs_review,verified,rejected'],
            'categories' => ['present', 'array'],
            'categories.*' => ['required', 'array:name,description,review_status'],
            'categories.*.name' => ['required', 'string', 'min:1', 'max:100'],
            'categories.*.description' => ['present', 'nullable', 'string'],
            'categories.*.review_status' => ['required', 'in:needs_review,verified,rejected'],
            'collections' => ['present', 'array'],
            'collections.*' => ['required', 'array:name,type,description,members,review_status'],
            'collections.*.name' => ['required', 'string', 'min:1'],
            'collections.*.type' => ['required', 'in:editorial,literary_series,fictional_universe,author_bibliography,thematic,personal'],
            'collections.*.description' => ['present', 'nullable', 'string'],
            'collections.*.members' => ['present', 'array'],
            'collections.*.members.*' => ['required', 'array:book_source_key,position'],
            'collections.*.members.*.book_source_key' => ['required', 'string', 'min:1'],
            'collections.*.members.*.position' => ['present', 'nullable', 'integer', 'min:1'],
            'collections.*.review_status' => ['required', 'in:needs_review,verified,rejected'],
            'books' => ['present', 'array'],
            'books.*' => ['required', 'array:source_key,title,subtitle,authors,isbn,publisher,edition,publication_year,language,description,cover_url,categories,collections,identifiers,metadata_provenance,review_status,enrichment_status'],
            'books.*.source_key' => ['required', 'string', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:-]*$/'],
            'books.*.title' => ['required', 'string', 'min:1'],
            'books.*.subtitle' => ['present', 'nullable', 'string'],
            'books.*.authors' => ['present', 'array'],
            'books.*.authors.*' => ['required', 'array:name'],
            'books.*.authors.*.name' => ['required', 'string', 'min:1'],
            'books.*.isbn' => ['present', 'nullable', 'string', 'max:20'],
            'books.*.publisher' => ['present', 'nullable', 'string'],
            'books.*.edition' => ['present', 'nullable', 'string', 'max:100'],
            'books.*.publication_year' => ['present', 'nullable', 'integer', 'between:1,32767'],
            'books.*.language' => ['present', 'nullable', 'string', 'max:10'],
            'books.*.description' => ['present', 'nullable', 'string'],
            'books.*.cover_url' => ['present', 'nullable', 'url', 'max:2048'],
            'books.*.categories' => ['present', 'array'],
            'books.*.categories.*' => ['required', 'string', 'min:1'],
            'books.*.collections' => ['present', 'array'],
            'books.*.collections.*' => ['required', 'array:name,type,position'],
            'books.*.collections.*.name' => ['required', 'string', 'min:1'],
            'books.*.collections.*.type' => ['required', 'in:editorial,literary_series,fictional_universe,author_bibliography,thematic,personal'],
            'books.*.collections.*.position' => ['present', 'nullable', 'integer', 'min:1'],
            'books.*.identifiers' => ['present', 'array'],
            'books.*.identifiers.*' => ['required', 'array:provider,type,value,url'],
            'books.*.identifiers.*.provider' => ['sometimes', 'required', 'string', 'min:1'],
            'books.*.identifiers.*.type' => ['required', 'string', 'min:1'],
            'books.*.identifiers.*.value' => ['required', 'string', 'min:1'],
            'books.*.identifiers.*.url' => ['sometimes', 'nullable', 'url'],
            'books.*.metadata_provenance' => ['present', 'array'],
            'books.*.metadata_provenance.provider' => ['sometimes', 'string', 'min:1'],
            'books.*.metadata_provenance.confidence' => ['sometimes', 'nullable'],
            'books.*.metadata_provenance.verified_fields' => ['sometimes', 'array'],
            'books.*.metadata_provenance.verified_fields.*' => ['string', 'in:source_key,title,subtitle,authors,isbn,publisher,edition,publication_year,language,description,cover_url,categories,collections,identifiers'],
            'books.*.metadata_provenance.proposed_fields' => ['sometimes', 'array'],
            'books.*.metadata_provenance.proposed_fields.*' => ['string', 'in:source_key,title,subtitle,authors,isbn,publisher,edition,publication_year,language,description,cover_url,categories,collections,identifiers'],
            'books.*.metadata_provenance.edition_notes' => ['sometimes', 'nullable', 'string'],
            'books.*.review_status' => ['required', 'in:needs_review,verified,rejected'],
            'books.*.enrichment_status' => ['required', 'in:pending,enriched,needs_review'],
        ]);

        $errors = array_merge_recursive($errors, $validation->errors()->toArray());

        if ($validation->fails()) {
            return $errors;
        }

        return [
            ...$errors,
            ...$this->referenceErrors($document),
            ...$this->provenanceErrors($document),
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function normalizeDocument(array $document): array
    {
        if (! is_array($document['collections'] ?? null) || ! is_array($document['books'] ?? null)) {
            return $document;
        }

        $collectionsByName = [];

        foreach ($document['collections'] as $collection) {
            if (is_array($collection) && is_string($collection['name'] ?? null)) {
                $collectionsByName[$this->normalizeName($collection['name'])] = $collection;
            }
        }

        foreach ($document['books'] as &$book) {
            if (! is_array($book)) {
                continue;
            }

            if (is_array($book['collections'] ?? null)) {
                foreach ($book['collections'] as &$reference) {
                    if (! is_string($reference)) {
                        continue;
                    }

                    $collection = $collectionsByName[$this->normalizeName($reference)] ?? null;
                    $reference = [
                        'name' => $reference,
                        'type' => is_array($collection) && is_string($collection['type'] ?? null) ? $collection['type'] : '',
                        'position' => null,
                    ];
                }

                unset($reference);
            }

            if (is_array($book['identifiers'] ?? null)) {
                foreach ($book['identifiers'] as &$identifier) {
                    if (is_array($identifier) && ! isset($identifier['provider']) && is_string($book['metadata_provenance']['provider'] ?? null)) {
                        $identifier['provider'] = $book['metadata_provenance']['provider'];
                    }
                }

                unset($identifier);
            }
        }

        unset($book);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, list<string>>
     */
    private function referenceErrors(array $document): array
    {
        $errors = [];
        $booksByKey = [];
        $authorsByName = $this->uniqueNames($document['authors'], 'authors', $errors);
        $categoriesByName = $this->uniqueNames($document['categories'], 'categories', $errors);
        $collectionsByName = $this->uniqueNames($document['collections'], 'collections', $errors);

        foreach ($document['books'] as $index => $book) {
            $sourceKey = $book['source_key'];

            if (isset($booksByKey[$sourceKey])) {
                $this->addError($errors, "books.$index.source_key", "Duplicate source key [$sourceKey].");
            }

            $booksByKey[$sourceKey] = $index;

            foreach ($book['authors'] as $authorIndex => $author) {
                if (! isset($authorsByName[$this->normalizeName($author['name'])])) {
                    $this->addError($errors, "books.$index.authors.$authorIndex.name", 'Author is not defined in the authors list.');
                }
            }

            foreach ($book['categories'] as $categoryIndex => $categoryName) {
                if (! isset($categoriesByName[$this->normalizeName($categoryName)])) {
                    $this->addError($errors, "books.$index.categories.$categoryIndex", 'Category is not defined in the categories list.');
                }
            }

            foreach ($book['collections'] as $collectionIndex => $collection) {
                $definedCollection = $collectionsByName[$this->normalizeName($collection['name'])] ?? null;

                if ($definedCollection === null) {
                    $this->addError($errors, "books.$index.collections.$collectionIndex.name", 'Collection is not defined in the collections list.');

                    continue;
                }

                if ($definedCollection['type'] !== $collection['type']) {
                    $this->addError($errors, "books.$index.collections.$collectionIndex.type", 'Collection type does not match its definition.');
                }
            }

            foreach ($book['identifiers'] as $identifierIndex => $identifier) {
                if (! isset($identifier['provider'])) {
                    $this->addError($errors, "books.$index.identifiers.$identifierIndex.provider", 'Identifier provider is required directly or through metadata_provenance.provider.');
                }
            }
        }

        foreach ($document['collections'] as $collectionIndex => $collection) {
            foreach ($collection['members'] as $memberIndex => $member) {
                if (! isset($booksByKey[$member['book_source_key']])) {
                    $this->addError($errors, "collections.$collectionIndex.members.$memberIndex.book_source_key", 'Book source key is not defined in the books list.');
                }
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, list<string>>
     */
    private function provenanceErrors(array $document): array
    {
        $errors = [];
        $bookFields = [
            'source_key',
            'title',
            'subtitle',
            'authors',
            'isbn',
            'publisher',
            'edition',
            'publication_year',
            'language',
            'description',
            'cover_url',
            'categories',
            'collections',
            'identifiers',
        ];
        $summaryFields = ['provider', 'confidence', 'verified_fields', 'proposed_fields', 'edition_notes'];

        foreach ($document['books'] as $bookIndex => $book) {
            $metadataProvenance = $book['metadata_provenance'];

            if (isset($metadataProvenance['confidence'])
                && ! is_string($metadataProvenance['confidence'])
                && ! is_numeric($metadataProvenance['confidence'])) {
                $this->addError($errors, "books.$bookIndex.metadata_provenance.confidence", 'Confidence must be a string, number, or null.');
            }

            foreach ($book['metadata_provenance'] as $field => $provenance) {
                if (in_array($field, $summaryFields, true)) {
                    continue;
                }

                if (! in_array($field, $bookFields, true)) {
                    $this->addError($errors, "books.$bookIndex.metadata_provenance.$field", 'Unknown provenance field.');

                    continue;
                }

                if (! is_array($provenance)) {
                    $this->addError($errors, "books.$bookIndex.metadata_provenance.$field", 'Field provenance must be an object.');

                    continue;
                }

                $unexpectedProperties = array_diff(array_keys($provenance), [
                    'provider',
                    'provider_record_id',
                    'source_url',
                    'retrieved_at',
                    'source_value',
                    'decision',
                    'confidence',
                ]);

                foreach ($unexpectedProperties as $property) {
                    $this->addError($errors, "books.$bookIndex.metadata_provenance.$field.$property", 'Unexpected field provenance property.');
                }

                foreach (['provider', 'provider_record_id', 'source_url', 'retrieved_at', 'source_value', 'decision', 'confidence'] as $requiredField) {
                    if (! array_key_exists($requiredField, $provenance)) {
                        $this->addError($errors, "books.$bookIndex.metadata_provenance.$field.$requiredField", 'Field provenance property is required.');
                    }
                }

                if (! is_string($provenance['provider'] ?? null)
                    || ! in_array($provenance['decision'] ?? null, ['proposed', 'accepted', 'rejected', 'needs_review'], true)
                    || ! is_string($provenance['retrieved_at'] ?? null)
                    || strtotime($provenance['retrieved_at']) === false
                    || (($provenance['source_url'] ?? null) !== null && filter_var($provenance['source_url'], FILTER_VALIDATE_URL) === false)
                    || (($provenance['confidence'] ?? null) !== null && (! is_numeric($provenance['confidence']) || $provenance['confidence'] < 0 || $provenance['confidence'] > 1))) {
                    $this->addError($errors, "books.$bookIndex.metadata_provenance.$field", 'Field provenance contains invalid provider, decision, or retrieval time.');
                }
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, list<string>>  $errors
     * @return array<string, array<string, mixed>>
     */
    private function uniqueNames(array $records, string $path, array &$errors): array
    {
        $recordsByName = [];

        foreach ($records as $index => $record) {
            $normalizedName = $this->normalizeName($record['name']);

            if (isset($recordsByName[$normalizedName])) {
                $this->addError($errors, "$path.$index.name", 'Name is duplicated in this list.');

                continue;
            }

            $recordsByName[$normalizedName] = $record;
        }

        return $recordsByName;
    }

    private function normalizeName(string $name): string
    {
        $lowercaseName = Str::lower(trim($name));

        return preg_replace('/\s+/u', ' ', $lowercaseName) ?? $lowercaseName;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function addError(array &$errors, string $path, string $message): void
    {
        $errors[$path][] = $message;
    }
}
