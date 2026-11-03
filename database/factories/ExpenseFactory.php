<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'description' => fake()->sentence(3),
            'amount_pence' => fake()->numberBetween(200, 12000),
            'expense_category_id' => ExpenseCategory::factory(),
        ];
    }
}
