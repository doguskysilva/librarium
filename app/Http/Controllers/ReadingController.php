<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingRequest;
use App\Http\Requests\TransitionReadingRequest;
use App\Http\Requests\UpdateReadingRequest;
use App\Models\Book;
use App\Models\Reading;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class ReadingController extends Controller
{
    public function create(Book $book): Response
    {
        return Inertia::render('readings/Create', ['book' => $book]);
    }

    public function store(StoreReadingRequest $request, Book $book): RedirectResponse
    {
        $reading = $book->readings()->create($request->validated());

        return to_route('readings.show', $reading);
    }

    public function show(Reading $reading, MarkdownRenderer $markdown): Response
    {
        $reading->load('book');
        $reading->setAttribute('review_html', $reading->review === null ? null : $markdown->toHtml($reading->review));
        $reading->setAttribute('private_notes_html', $reading->private_notes === null ? null : $markdown->toHtml($reading->private_notes));

        return Inertia::render('readings/Show', ['reading' => $reading]);
    }

    public function edit(Reading $reading): Response
    {
        $reading->load('book');

        return Inertia::render('readings/Edit', ['reading' => $reading]);
    }

    public function update(UpdateReadingRequest $request, Reading $reading): RedirectResponse
    {
        $reading->update($request->validated());

        return to_route('readings.show', $reading);
    }

    public function start(TransitionReadingRequest $request, Reading $reading): RedirectResponse
    {
        $reading->start($request->datePayload());

        return back();
    }

    public function pause(Reading $reading): RedirectResponse
    {
        $reading->pause();

        return back();
    }

    public function resume(Reading $reading): RedirectResponse
    {
        $reading->resume();

        return back();
    }

    public function complete(TransitionReadingRequest $request, Reading $reading): RedirectResponse
    {
        $reading->complete($request->datePayload());

        return back();
    }

    public function skip(TransitionReadingRequest $request, Reading $reading): RedirectResponse
    {
        $reading->skip($request->datePayload());

        return back();
    }

    public function abandon(TransitionReadingRequest $request, Reading $reading): RedirectResponse
    {
        $reading->abandon($request->datePayload());

        return back();
    }
}
