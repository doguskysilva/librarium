<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'subtitle',
    'isbn',
    'publisher',
    'edition',
    'publication_year',
    'language',
    'description',
    'cover_path',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, HasUuids;

    /**
     * The key type used by the model.
     */
    protected $keyType = 'string';

    /**
     * Indicate that the IDs are not auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
        ];
    }
}
