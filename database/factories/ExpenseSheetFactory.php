<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseSheet>
 */
class ExpenseSheetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'created_by' => User::factory(),
            'form_id' => Form::factory(),
            'department_id' => Department::factory(),
            'status' => 'En attente',
            'total' => $this->faker->randomFloat(2, 10, 500),
            'is_draft' => false,
            'approved' => null,
            'distance' => null,
            'route' => null,
            'validated_at' => null,
            'validated_by' => null,
            'refusal_reason' => null,
        ];
    }
}
