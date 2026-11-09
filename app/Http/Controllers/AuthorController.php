<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuthorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('authors/Index', [
            'authors' => Author::query()->latest()->paginate(12),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('authors/Create');
    }

    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        $author = Author::create($request->validated());

        return to_route('authors.show', $author);
    }

    public function show(Author $author): Response
    {
        $author->load('books');

        return Inertia::render('authors/Show', ['author' => $author]);
    }

    public function edit(Author $author): Response
    {
        return Inertia::render('authors/Edit', ['author' => $author]);
    }

    public function update(UpdateAuthorRequest $request, Author $author): RedirectResponse
    {
        $author->update($request->validated());

        return to_route('authors.show', $author);
    }

    public function destroy(Author $author): RedirectResponse
    {
        $author->delete();

        return to_route('authors.index');
    }
}
