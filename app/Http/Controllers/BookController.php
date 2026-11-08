<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\RedirectResponse;
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
        return Inertia::render('books/Create');
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $book = Book::create($request->validated());

        return to_route('books.show', $book);
    }

    public function show(Book $book): Response
    {
        return Inertia::render('books/Show', [
            'book' => $book,
        ]);
    }

    public function edit(Book $book): Response
    {
        return Inertia::render('books/Edit', [
            'book' => $book,
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $book->update($request->validated());

        return to_route('books.show', $book);
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return to_route('books.index');
    }
}
