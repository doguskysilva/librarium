<?php

namespace App\Services\BookMetadata;

use App\Contracts\BookMetadataProvider;
use App\Data\BookMetadata;
use Illuminate\Support\Facades\Http;

class OpenLibraryProvider implements BookMetadataProvider
{
    public function key(): string
    {
        return 'open_library';
    }

    public function findByIsbn(string $isbn): ?BookMetadata
    {
        $email = config('services.open_library.email');
        $email = is_string($email) && $email !== '' ? $email : 'unspecified';

        $response = Http::acceptJson()
            ->withUserAgent("Librarium ({$email})")
            ->connectTimeout(3)
            ->timeout(8)
            ->get('https://openlibrary.org/search.json', [
                'isbn' => $isbn,
                'fields' => 'title,subtitle,author_name,isbn,publisher,first_publish_year,language,cover_i',
                'limit' => 1,
            ])
            ->throw()
            ->json();

        $book = data_get($response, 'docs.0');

        if (! is_array($book) || ! is_string($book['title'] ?? null) || trim($book['title']) === '') {
            return null;
        }

        $coverId = $book['cover_i'] ?? null;

        return new BookMetadata(
            provider: 'open_library',
            title: trim($book['title']),
            subtitle: $this->stringValue($book['subtitle'] ?? null),
            authors: $this->stringList($book['author_name'] ?? []),
            isbn: $this->isbnValue($book['isbn'] ?? [], $isbn),
            publisher: $this->firstString($book['publisher'] ?? null),
            edition: null,
            publicationYear: is_int($book['first_publish_year'] ?? null) ? $book['first_publish_year'] : null,
            language: $this->firstString($book['language'] ?? null),
            description: null,
            coverUrl: is_int($coverId) && $coverId > 0
                ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg"
                : null,
        );
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function firstString(mixed $values): ?string
    {
        if (is_string($values)) {
            return $this->stringValue($values);
        }

        if (is_array($values)) {
            foreach ($values as $value) {
                $string = $this->stringValue($value);

                if ($string !== null) {
                    return $string;
                }
            }
        }

        return null;
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

    private function isbnValue(mixed $isbns, string $requestedIsbn): ?string
    {
        if (! is_array($isbns)) {
            return null;
        }

        foreach ($isbns as $isbn) {
            if (is_string($isbn) && preg_replace('/[\s-]/', '', $isbn) === preg_replace('/[\s-]/', '', $requestedIsbn)) {
                return $isbn;
            }
        }

        foreach ($isbns as $isbn) {
            if (is_string($isbn) && trim($isbn) !== '') {
                return trim($isbn);
            }
        }

        return null;
    }
}
