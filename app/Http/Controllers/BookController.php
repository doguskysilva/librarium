<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var array{category?: string, language?: string, filter?: string, year_from?: int, year_to?: int} $filters */
        $filters = $request->validate([
            'category' => ['nullable', 'uuid', 'exists:categories,id'],
            'language' => ['nullable', 'string', 'max:10'],
            'filter' => ['nullable', 'in:missing_covers,unclassified,missing_isbn,missing_descriptions,unknown_language,unknown_year'],
            'year_from' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'year_to' => ['nullable', 'integer', 'min:1', 'max:32767'],
        ]);
        if (isset($filters['year_from'])) {
            $filters['year_from'] = (int) $filters['year_from'];
        }
        if (isset($filters['year_to'])) {
            $filters['year_to'] = (int) $filters['year_to'];
        }

        $yearFrom = $filters['year_from'] ?? null;
        $yearTo = $filters['year_to'] ?? null;

        $books = Book::query()
            ->when(
                $filters['category'] ?? null,
                fn (Builder $query, string $categoryId) => $query->whereHas(
                    'categories',
                    fn (Builder $categories) => $categories->whereKey($categoryId),
                ),
            )
            ->when(
                $filters['language'] ?? null,
                fn (Builder $query, string $language) => $query->where('language', $language),
            );

        if ($yearFrom !== null) {
            $books->where('publication_year', '>=', $yearFrom);
        }
        if ($yearTo !== null) {
            $books->where('publication_year', '<=', $yearTo);
        }

        $books = match ($filters['filter'] ?? null) {
            'missing_covers' => $books
                ->where(fn (Builder $query) => $query->whereNull('cover_path')->orWhereRaw("TRIM(cover_path) = ''"))
                ->where(fn (Builder $query) => $query->whereNull('cover_url')->orWhereRaw("TRIM(cover_url) = ''")),
            'unclassified' => $books->doesntHave('categories'),
            'missing_isbn' => $books->where(fn (Builder $query) => $query->whereNull('isbn')->orWhereRaw("TRIM(isbn) = ''")),
            'missing_descriptions' => $books->where(fn (Builder $query) => $query->whereNull('description')->orWhereRaw("TRIM(description) = ''")),
            'unknown_language' => $books->where(fn (Builder $query) => $query->whereNull('language')->orWhereRaw("TRIM(language) = ''")),
            'unknown_year' => $books->whereNull('publication_year'),
            default => $books,
        };

        return Inertia::render('books/Index', [
            'books' => $books->latest()->paginate(12)->withQueryString(),
            'filters' => $filters,
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
        $book->load([
            'authors',
            'categories',
            'collections',
            'readings' => fn ($query) => $query->latest(),
        ]);

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
