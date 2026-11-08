<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('categories/Index', [
            'categories' => Category::query()->latest()->paginate(12),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('categories/Create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return to_route('categories.show', $category);
    }

    public function show(Category $category): Response
    {
        return Inertia::render('categories/Show', ['category' => $category]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('categories/Edit', ['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categories.show', $category);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return to_route('categories.index');
    }
}
