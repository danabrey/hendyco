<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Seed a few starter expense categories.
     */
    public function run(): void
    {
        foreach (['Supplies', 'Insurance', 'Advertising', 'Training', 'Equipment'] as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
