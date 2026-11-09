<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $totalBooks = Book::query()->count();
        $percentage = static fn (int $count): float => $totalBooks === 0
            ? 0.0
            : round(($count / $totalBooks) * 100, 2);

        $booksByCategory = DB::table('categories')
            ->join('book_category', 'categories.id', '=', 'book_category.category_id')
            ->select('categories.id', 'categories.name')
            ->selectRaw('COUNT(DISTINCT book_category.book_id) as books_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('books_count')
            ->orderBy('categories.name')
            ->limit(10)
            ->get()
            ->map(static fn (stdClass $category): array => [
                'id' => (string) $category->id,
                'name' => (string) $category->name,
                'count' => (int) $category->books_count,
                'percentage' => $percentage((int) $category->books_count),
            ])
            ->all();

        $languageGroups = DB::table('books')
            ->selectRaw("NULLIF(TRIM(language), '') as language, COUNT(*) as books_count")
            ->groupByRaw("NULLIF(TRIM(language), '')")
            ->orderByDesc('books_count')
            ->orderBy('language')
            ->get()
            ->map(static fn (stdClass $language): array => [
                'language' => $language->language === null ? null : (string) $language->language,
                'label' => $language->language === null ? 'Unknown' : (string) $language->language,
                'count' => (int) $language->books_count,
                'percentage' => $percentage((int) $language->books_count),
            ])
            ->all();

        if (count($languageGroups) > 8) {
            $otherLanguages = array_slice($languageGroups, 7);
            $otherCount = array_sum(array_column($otherLanguages, 'count'));
            $languageGroups = [
                ...array_slice($languageGroups, 0, 7),
                [
                    'language' => null,
                    'label' => 'Other',
                    'count' => $otherCount,
                    'percentage' => $percentage($otherCount),
                    'members' => $otherLanguages,
                ],
            ];
        }

        $publicationTimeline = DB::table('books')
            ->whereNotNull('publication_year')
            ->selectRaw('CAST(publication_year / 10 AS INTEGER) * 10 as decade_start, COUNT(*) as books_count')
            ->groupByRaw('CAST(publication_year / 10 AS INTEGER) * 10')
            ->orderBy('decade_start')
            ->get()
            ->map(static fn (stdClass $decade): array => [
                'decadeStart' => (int) $decade->decade_start,
                'label' => ((int) $decade->decade_start).'–'.(((int) $decade->decade_start) + 9),
                'count' => (int) $decade->books_count,
            ])
            ->all();

        $unknownYearCount = Book::query()->whereNull('publication_year')->count();
        if ($unknownYearCount > 0) {
            $publicationTimeline[] = [
                'decadeStart' => null,
                'label' => 'Unknown year',
                'count' => $unknownYearCount,
            ];
        }

        return Inertia::render('Dashboard', [
            'summary' => [
                'books' => $totalBooks,
                'authors' => Author::query()->count(),
                'collections' => Collection::query()->count(),
            ],
            'composition' => [
                'booksByCategory' => $booksByCategory,
                'booksByLanguage' => $languageGroups,
            ],
            'catalogHealth' => [
                'missingCovers' => Book::query()
                    ->where(fn ($query) => $query->whereNull('cover_path')->orWhereRaw("TRIM(cover_path) = ''"))
                    ->where(fn ($query) => $query->whereNull('cover_url')->orWhereRaw("TRIM(cover_url) = ''"))
                    ->count(),
                'unclassified' => Book::query()->doesntHave('categories')->count(),
                'missingIsbn' => Book::query()
                    ->whereNull('isbn')
                    ->orWhereRaw("TRIM(isbn) = ''")
                    ->count(),
                'missingDescriptions' => Book::query()
                    ->whereNull('description')
                    ->orWhereRaw("TRIM(description) = ''")
                    ->count(),
            ],
            'publicationTimeline' => $publicationTimeline,
        ]);
    }
}
