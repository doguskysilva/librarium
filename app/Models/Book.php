<?php

namespace App\Models;

use App\Support\Isbn;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $isbn
 * @property-read string|null $canonical_isbn13
 */
#[Fillable([
    'title',
    'subtitle',
    'translator',
    'isbn',
    'publisher',
    'edition',
    'publication_year',
    'language',
    'description',
    'cover_path',
    'cover_url',
    'source_key',
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

    /** @return Attribute<string|null, string|null> */
    protected function isbn(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null
                ? (Isbn::toIsbn13($value) ?? $value)
                : $value,
        );
    }

    /** @return Attribute<string|null, never> */
    protected function canonicalIsbn13(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->isbn === null
                ? null
                : Isbn::toIsbn13($this->isbn),
        );
    }

    /** @return HasMany<Reading, $this> */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    /** @return HasMany<BookExternalIdentifier, $this> */
    public function externalIdentifiers(): HasMany
    {
        return $this->hasMany(BookExternalIdentifier::class);
    }

    /** @return BelongsToMany<Author, $this> */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)->withTimestamps();
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /** @return BelongsToMany<Collection, $this> */
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
