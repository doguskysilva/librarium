<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Database\QueryException;

test('books can be related to authors, categories, and collections', function () {
    $book = Book::factory()->create();
    $author = Author::factory()->create();
    $category = Category::factory()->create();
    $collection = Collection::factory()->create();

    $book->authors()->attach($author);
    $book->categories()->attach($category);
    $book->collections()->attach($collection, ['position' => 1]);

    expect($book->fresh()->authors)->toHaveCount(1)
        ->and($book->fresh()->categories)->toHaveCount(1)
        ->and($book->fresh()->collections)->toHaveCount(1);

    $this->assertDatabaseHas('author_book', [
        'book_id' => $book->id,
        'author_id' => $author->id,
    ]);
    $this->assertDatabaseHas('book_category', [
        'book_id' => $book->id,
        'category_id' => $category->id,
    ]);
    $this->assertDatabaseHas('collection_book', [
        'book_id' => $book->id,
        'collection_id' => $collection->id,
        'position' => 1,
    ]);
});

test('collection books are ordered by position with unpositioned books last', function () {
    $collection = Collection::factory()->create();
    $unpositionedBook = Book::factory()->create();
    $firstBook = Book::factory()->create();
    $secondBook = Book::factory()->create();

    $collection->books()->attach($unpositionedBook, ['position' => null]);
    $collection->books()->attach($secondBook, ['position' => 2]);
    $collection->books()->attach($firstBook, ['position' => 1]);

    expect($collection->fresh()->books->pluck('id')->all())
        ->toBe([$firstBook->id, $secondBook->id, $unpositionedBook->id]);
});

test('duplicate book relationships are rejected by pivot constraints', function () {
    $book = Book::factory()->create();
    $author = Author::factory()->create();

    $book->authors()->attach($author);

    expect(fn () => $book->authors()->attach($author))
        ->toThrow(QueryException::class);
});

test('deleting a book cascades to all relationship pivots', function () {
    $book = Book::factory()->create();
    $book->authors()->attach(Author::factory()->create());
    $book->categories()->attach(Category::factory()->create());
    $book->collections()->attach(Collection::factory()->create());

    $book->delete();

    $this->assertDatabaseCount('author_book', 0);
    $this->assertDatabaseCount('book_category', 0);
    $this->assertDatabaseCount('collection_book', 0);
});

test('deleting an author leaves related books intact', function () {
    $book = Book::factory()->create();
    $author = Author::factory()->create();
    $author->books()->attach($book);

    $author->delete();

    $this->assertDatabaseHas('books', ['id' => $book->id]);
    $this->assertDatabaseCount('author_book', 0);
});
