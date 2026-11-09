<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('collections/Index', [
            'collections' => Collection::query()->latest()->paginate(12),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('collections/Create');
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $collection = Collection::create($request->validated());

        return to_route('collections.show', $collection);
    }

    public function show(Collection $collection): Response
    {
        $collection->load('books');

        return Inertia::render('collections/Show', ['collection' => $collection]);
    }

    public function edit(Collection $collection): Response
    {
        return Inertia::render('collections/Edit', ['collection' => $collection]);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $collection->update($request->validated());

        return to_route('collections.show', $collection);
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return to_route('collections.index');
    }
}
