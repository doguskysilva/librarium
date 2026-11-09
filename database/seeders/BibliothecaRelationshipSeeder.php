<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Database\Seeder;

class BibliothecaRelationshipSeeder extends Seeder
{
    /**
     * Seed the Bibliotheca entity relationships.
     */
    public function run(): void
    {
        $authors = Author::query()->get();
        $books = Book::query()->get();
        $categories = Category::query()->get();
        $collections = Collection::query()->get();

        if ($books->isEmpty() || $authors->isEmpty() || $categories->isEmpty() || $collections->isEmpty()) {
            return;
        }

        $books->values()->each(function (Book $book, int $index) use ($authors, $categories, $collections): void {
            $book->authors()->sync($authors->take(2)->modelKeys());
            $book->categories()->sync($categories->take(2)->modelKeys());

            $collection = $collections->get($index % $collections->count());

            $book->collections()->sync([
                $collection->getKey() => ['position' => $index + 1],
            ]);
        });
    }
}
