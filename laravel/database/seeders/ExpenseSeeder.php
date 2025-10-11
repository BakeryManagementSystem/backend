<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\User;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owners = User::where('user_type', 'owner')->get();

        if ($owners->isEmpty()) {
            $this->command->info('No owners found. Skipping expenses seeding.');
            return;
        }

        $categories = ['Ingredients', 'Utilities', 'Rent', 'Equipment', 'Marketing', 'Transportation', 'Packaging', 'Maintenance'];
        $statuses = ['pending', 'approved', 'paid'];
        $suppliers = ['ABC Supplies', 'Fresh Foods Co.', 'Bakery Essentials', 'Metro Wholesale', 'Premium Ingredients Ltd.'];

        foreach ($owners as $owner) {
            // Create 5-10 expenses per owner
            $expenseCount = rand(5, 10);

            for ($i = 0; $i < $expenseCount; $i++) {
                $category = $categories[array_rand($categories)];
                $ingredient = null;

                // If category is Ingredients, try to link to an ingredient
                if ($category === 'Ingredients') {
                    $ingredient = Ingredient::inRandomOrder()->first();
                }

                Expense::create([
                    'user_id' => $owner->id,
                    'category' => $category,
                    'description' => 'Expense for ' . $category,
                    'amount' => rand(50, 500) + (rand(0, 99) / 100),
                    'expense_date' => Carbon::now()->subDays(rand(1, 90)),
                    'receipt_path' => null,
                    'supplier_name' => $suppliers[array_rand($suppliers)],
                    'ingredient_id' => $ingredient ? $ingredient->id : null,
                    'notes' => 'Monthly ' . strtolower($category) . ' expense',
                    'status' => $statuses[array_rand($statuses)],
                ]);
            }
        }

        $this->command->info('Expenses seeded successfully!');
    }
}

