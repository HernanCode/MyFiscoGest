<?php

namespace Database\Factories;

use App\Models\Movement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    protected $model = Movement::class;

    public function definition(): array
    {
        return [
            // fiscal_year_id, quarter, vat_amount, irpf_amount y total_amount
            // se omiten a propósito: los calcula el hook `saving` de Movement.
            'type' => $this->faker->randomElement(['income', 'expense']),
            'category_id' => null,
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'concept' => $this->faker->realText(40),
            'counterparty' => $this->faker->company(),
            'invoice_number' => $this->faker->numerify('F-####'),
            'base_amount' => $this->faker->randomFloat(2, 10, 2000),
            'vat_rate' => $this->faker->randomElement([0, 10, 21]),
            'irpf_rate' => 0,
            'deductible_pct' => 100,
            'payment_status' => 'pending',
            'paid_at' => null,
            'invoice_path' => null,
            'notes' => null,
        ];
    }
}
