<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('books/Index', [
            'books' => Book::query()->latest()->paginate(12),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('books/Create', $this->relationOptions());
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $book = Book::create(Arr::except($validated, $this->relationshipFields()));
        $this->syncRelationships($book, $validated);

        return to_route('books.show', $book);
    }

    public function show(Book $book): Response
    {
        $book->load(['authors', 'categories', 'collections']);

        return Inertia::render('books/Show', [
            'book' => $book,
        ]);
    }

    public function edit(Book $book): Response
    {
        $book->load(['authors', 'categories', 'collections']);

        return Inertia::render('books/Edit', [
            'book' => $book,
            ...$this->relationOptions(),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        $book->update(Arr::except($validated, $this->relationshipFields()));
        $this->syncRelationships($book, $validated);

        return to_route('books.show', $book);
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return to_route('books.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function relationOptions(): array
    {
        return [
            'authors' => Author::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'collections' => Collection::query()->orderBy('name')->get(['id', 'name', 'type']),
        ];
    }

    /**
     * @return list<string>
     */
    private function relationshipFields(): array
    {
        return ['author_ids', 'category_ids', 'collection_ids'];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRelationships(Book $book, array $validated): void
    {
        $book->authors()->sync($validated['author_ids'] ?? []);
        $book->categories()->sync($validated['category_ids'] ?? []);
        $book->collections()->sync($validated['collection_ids'] ?? []);
    }
}
