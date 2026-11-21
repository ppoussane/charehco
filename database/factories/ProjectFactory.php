<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'summary' => fake()->sentence(12),
            'body' => fake()->paragraphs(3, true),
            'location' => fake()->city(),
            'year' => fake()->numberBetween(2015, 2026),
            'category' => 'Residential',
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false, 'published_at' => null]);
    }
}
