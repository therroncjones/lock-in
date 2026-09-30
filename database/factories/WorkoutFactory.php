<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'performed_on' => now()->toDateString(),
            'completed_at' => now(),
        ];
    }

    public function incomplete(): static
    {
        return $this->state(fn (): array => [
            'completed_at' => null,
        ]);
    }
}
