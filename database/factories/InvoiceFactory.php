<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-30 days', 'now');
        $dueDate = (clone $issueDate)->modify('+30 days');

        return [
            'user_id' => \App\Models\User::factory(),
            'invoice_number' => fake()->unique()->numerify('INV-####-####'),
            'recipient_name' => fake()->company(),
            'recipient_email' => fake()->companyEmail(),
            'recipient_address' => fake()->address(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'gst' => 0,
            'currency' => fake()->randomElement(array_column(\App\Enums\Currencies::cases(), 'value')),
            'description' => fake()->sentence(),
            'line_items' => [
                [
                    'description' => fake()->sentence(3),
                    'quantity' => fake()->numberBetween(1, 10),
                    'unit_price' => fake()->randomFloat(2, 50, 1000),
                    'unit_type' => 'quantity',
                ],
            ],
            'invoice_status_id' => \App\Models\InvoiceStatus::inRandomOrder()->first()?->id ?? \App\Models\InvoiceStatus::factory(),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'is_recurring' => false,
            'recurring_frequency_id' => null,
            'next_recurring_date' => null,
            'last_sent_at' => null,
        ];
    }

    /**
     * Indicate that the invoice is recurring.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurring_frequency_id' => \App\Models\RecurringFrequency::inRandomOrder()->first()?->id,
            'next_recurring_date' => fake()->dateTimeBetween('+1 day', '+90 days'),
        ]);
    }

    /**
     * Indicate that the invoice has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sent_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
