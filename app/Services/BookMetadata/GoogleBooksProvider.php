<?php

namespace App\Services\BookMetadata;

use App\Contracts\BookMetadataProvider;
use App\Data\BookMetadata;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleBooksProvider implements BookMetadataProvider
{
    public function key(): string
    {
        return 'google_books';
    }

    public function findByIsbn(string $isbn): ?BookMetadata
    {
        $query = ['q' => 'isbn:'.$isbn];
        $apiKey = config('services.google_books.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $query['key'] = $apiKey;
        }

        $response = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->get('https://www.googleapis.com/books/v1/volumes', $query)
            ->throw()
            ->json();

        $volume = data_get($response, 'items.0.volumeInfo');

        if (! is_array($volume) || ! is_string($volume['title'] ?? null) || trim($volume['title']) === '') {
            return null;
        }

        $identifiers = $volume['industryIdentifiers'] ?? [];
        $coverUrl = data_get($volume, 'imageLinks.thumbnail');

        return new BookMetadata(
            provider: 'google_books',
            title: trim($volume['title']),
            subtitle: $this->stringValue($volume['subtitle'] ?? null),
            authors: $this->stringList($volume['authors'] ?? []),
            isbn: $this->googleIsbn($identifiers),
            publisher: $this->stringValue($volume['publisher'] ?? null),
            edition: $this->stringValue($volume['edition'] ?? null),
            publicationYear: $this->publicationYear($volume['publishedDate'] ?? null),
            language: $this->stringValue($volume['language'] ?? null),
            description: $this->stringValue($volume['description'] ?? null),
            coverUrl: is_string($coverUrl) && $coverUrl !== ''
                ? Str::of($coverUrl)->replaceStart('http://', 'https://')->toString()
                : null,
        );
    }

    private function googleIsbn(mixed $identifiers): ?string
    {
        if (! is_array($identifiers)) {
            return null;
        }

        foreach (['ISBN_13', 'ISBN_10'] as $type) {
            foreach ($identifiers as $identifier) {
                if (
                    is_array($identifier)
                    && ($identifier['type'] ?? null) === $type
                    && is_string($identifier['identifier'] ?? null)
                ) {
                    return $identifier['identifier'];
                }
            }
        }

        return null;
    }

    private function publicationYear(mixed $publishedDate): ?int
    {
        if (! is_string($publishedDate) || preg_match('/^\d{4}/', $publishedDate, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            $values,
            fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));
    }
}
