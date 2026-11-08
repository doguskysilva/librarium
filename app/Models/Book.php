<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_book', 'book_id', 'collection_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByRaw('collection_book.position IS NULL, collection_book.position');
    }

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
