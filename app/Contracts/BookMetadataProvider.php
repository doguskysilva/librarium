<?php

namespace App\Contracts;

use App\Data\BookMetadata;

interface BookMetadataProvider
{
    public function key(): string;

    public function findByIsbn(string $isbn): ?BookMetadata;
}
