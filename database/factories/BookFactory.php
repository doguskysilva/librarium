<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->optional()->sentence(3),
            'isbn' => fake()->optional()->numerify('978#############'),
            'publisher' => fake()->optional()->company(),
            'edition' => fake()->optional()->randomElement(['1st edition', '2nd edition', 'Revised edition']),
            'publication_year' => fake()->optional()->numberBetween(1900, now()->year),
            'language' => fake()->optional()->randomElement(['en', 'pt-BR', 'es', 'fr']),
            'description' => fake()->optional()->paragraph(),
            'cover_path' => fake()->optional()->regexify('covers/[a-z0-9]{12}\.jpg'),
        ];
    }
}
