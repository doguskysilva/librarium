<?php

namespace App\Models;

use App\Enums\CollectionType;
use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'type', 'description'])]
class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'collection_book', 'collection_id', 'book_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByRaw('collection_book.position IS NULL, collection_book.position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CollectionType::class,
        ];
    }
}
