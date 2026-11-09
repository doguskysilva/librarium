<?php

namespace Database\Factories;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reading>
 */
class ReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'status' => ReadingStatus::Queued,
            'started_on' => null,
            'started_year' => null,
            'finished_on' => null,
            'finished_year' => null,
            'rating' => null,
            'review' => fake()->optional()->paragraphs(2, true),
            'private_notes' => fake()->optional()->sentence(),
        ];
    }
}
