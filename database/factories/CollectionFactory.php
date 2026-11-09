<?php

namespace Database\Factories;

use App\Enums\CollectionType;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(CollectionType::cases()),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
