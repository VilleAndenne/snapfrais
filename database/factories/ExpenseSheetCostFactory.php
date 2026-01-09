<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseSheetCost>
 */
class ExpenseSheetCostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_sheet_id' => \App\Models\ExpenseSheet::factory(),
            'form_cost_id' => \App\Models\FormCost::factory(),
            'type' => fake()->randomElement(['km', 'fixed', 'percentage']),
            'distance' => null,
            'google_distance' => null,
            'route' => null,
            'requirements' => null,
            'total' => fake()->randomFloat(2, 10, 500),
            'amount' => null,
            'date' => fake()->date(),
        ];
    }
}
