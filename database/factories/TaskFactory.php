<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(1, 10),
            'reward_amount' => fake()->randomFloat(2, 0.1, 10),
            'external_url' => fake()->url(),
            'is_active' => true,
        ];
    }
}
