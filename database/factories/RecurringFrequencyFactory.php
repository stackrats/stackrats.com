<?php

namespace Database\Factories;

use App\Enums\RecurringFrequencies;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringFrequency>
 */
class RecurringFrequencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                RecurringFrequencies::WEEKLY->value,
                RecurringFrequencies::MONTHLY->value,
                RecurringFrequencies::QUARTERLY->value,
                RecurringFrequencies::YEARLY->value,
            ]),
            'sort_order' => fake()->numberBetween(1, 4),
        ];
    }
}
