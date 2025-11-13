<?php

namespace Database\Factories;

use App\Enums\InvoiceStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceStatus>
 */
class InvoiceStatusFactory extends Factory
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
                InvoiceStatuses::DRAFT->value,
                InvoiceStatuses::SENT->value,
                InvoiceStatuses::PAID->value,
                InvoiceStatuses::OVERDUE->value,
                InvoiceStatuses::CANCELLED->value,
            ]),
            'sort_order' => fake()->numberBetween(1, 5),
        ];
    }
}
