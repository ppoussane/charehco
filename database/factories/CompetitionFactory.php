<?php

namespace Database\Factories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'location' => fake()->city(),
            'prize' => '€10,000',
            'starts_at' => now()->subWeek(),
            'deadline_at' => now()->addMonth(),
            'results_at' => now()->addMonths(2),
            'anonymous_judging' => true,
            'is_published' => true,
        ];
    }

    public function closedForEntries(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonths(2),
            'deadline_at' => now()->subDay(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addWeek(),
            'deadline_at' => now()->addMonths(2),
        ]);
    }
}
