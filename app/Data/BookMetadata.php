<?php

namespace App\Data;

final readonly class BookMetadata
{
    /**
     * @param  array<int, string>  $authors
     */
    public function __construct(
        public string $provider,
        public string $title,
        public ?string $subtitle,
        public array $authors,
        public ?string $isbn,
        public ?string $publisher,
        public ?string $edition,
        public ?int $publicationYear,
        public ?string $language,
        public ?string $description,
        public ?string $coverUrl,
    ) {}
}
