<?php

namespace Database\Factories;

use App\Models\RecurringMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringMovement>
 */
class RecurringMovementFactory extends Factory
{
    protected $model = RecurringMovement::class;

    public function definition(): array
    {
        return [
            // category_id, next_date y los importes calculados en movements
            // se comportan igual que en MovementFactory.
            'type' => $this->faker->randomElement(['income', 'expense']),
            'category_id' => null,
            'concept' => $this->faker->realText(40),
            'counterparty' => $this->faker->company(),
            'base_amount' => $this->faker->randomFloat(2, 10, 500),
            'vat_rate' => $this->faker->randomElement([0, 10, 21]),
            'irpf_rate' => 0,
            'deductible_pct' => 100,
            'start_date' => '2026-01-01',
            'day_of_month' => 5,
            'ends_on' => null,
            'next_date' => '2026-01-05',
            'active' => true,
            'notes' => null,
        ];
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => 'expense']);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
