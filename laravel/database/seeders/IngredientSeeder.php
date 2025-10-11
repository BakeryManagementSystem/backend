<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingredient;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $ingredients = [
            // Flours
            ['name' => 'All-Purpose Flour', 'unit' => 'kg', 'current_unit_price' => 3.50, 'category' => 'flours', 'status' => true],
            ['name' => 'Bread Flour', 'unit' => 'kg', 'current_unit_price' => 4.25, 'category' => 'flours', 'status' => true],
            ['name' => 'Cake Flour', 'unit' => 'kg', 'current_unit_price' => 5.75, 'category' => 'flours', 'status' => true],
            ['name' => 'Whole Wheat Flour', 'unit' => 'kg', 'current_unit_price' => 4.50, 'category' => 'flours', 'status' => true],

            // Dairy
            ['name' => 'Butter (Unsalted)', 'unit' => 'kg', 'current_unit_price' => 8.50, 'category' => 'dairy', 'status' => true],
            ['name' => 'Heavy Cream', 'unit' => 'liter', 'current_unit_price' => 6.75, 'category' => 'dairy', 'status' => true],
            ['name' => 'Milk (Whole)', 'unit' => 'liter', 'current_unit_price' => 3.25, 'category' => 'dairy', 'status' => true],
            ['name' => 'Cream Cheese', 'unit' => 'kg', 'current_unit_price' => 12.00, 'category' => 'dairy', 'status' => true],

            // Eggs
            ['name' => 'Large Eggs', 'unit' => 'dozen', 'current_unit_price' => 4.50, 'category' => 'eggs', 'status' => true],

            // Sweeteners
            ['name' => 'Granulated Sugar', 'unit' => 'kg', 'current_unit_price' => 2.75, 'category' => 'sweeteners', 'status' => true],
            ['name' => 'Brown Sugar', 'unit' => 'kg', 'current_unit_price' => 3.25, 'category' => 'sweeteners', 'status' => true],
            ['name' => 'Powdered Sugar', 'unit' => 'kg', 'current_unit_price' => 4.00, 'category' => 'sweeteners', 'status' => true],
            ['name' => 'Honey', 'unit' => 'kg', 'current_unit_price' => 12.50, 'category' => 'sweeteners', 'status' => true],

            // Leavening
            ['name' => 'Active Dry Yeast', 'unit' => 'kg', 'current_unit_price' => 15.00, 'category' => 'leavening', 'status' => true],
            ['name' => 'Baking Powder', 'unit' => 'kg', 'current_unit_price' => 12.00, 'category' => 'leavening', 'status' => true],
            ['name' => 'Baking Soda', 'unit' => 'kg', 'current_unit_price' => 8.50, 'category' => 'leavening', 'status' => true],

            // Chocolate
            ['name' => 'Dark Chocolate (70%)', 'unit' => 'kg', 'current_unit_price' => 22.00, 'category' => 'chocolate', 'status' => true],
            ['name' => 'Milk Chocolate', 'unit' => 'kg', 'current_unit_price' => 18.50, 'category' => 'chocolate', 'status' => true],
            ['name' => 'Cocoa Powder', 'unit' => 'kg', 'current_unit_price' => 16.75, 'category' => 'chocolate', 'status' => true],

            // Nuts & Seeds
            ['name' => 'Almonds (Sliced)', 'unit' => 'kg', 'current_unit_price' => 24.00, 'category' => 'nuts', 'status' => true],
            ['name' => 'Walnuts', 'unit' => 'kg', 'current_unit_price' => 28.50, 'category' => 'nuts', 'status' => true],

            // Spices & Flavorings
            ['name' => 'Vanilla Extract', 'unit' => 'liter', 'current_unit_price' => 85.00, 'category' => 'spices', 'status' => true],
            ['name' => 'Cinnamon (Ground)', 'unit' => 'kg', 'current_unit_price' => 45.00, 'category' => 'spices', 'status' => true],

            // Other
            ['name' => 'Salt (Fine)', 'unit' => 'kg', 'current_unit_price' => 2.25, 'category' => 'other', 'status' => true],
            ['name' => 'Vegetable Oil', 'unit' => 'liter', 'current_unit_price' => 4.50, 'category' => 'other', 'status' => true],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::updateOrCreate(
                ['name' => $ingredient['name']],
                $ingredient
            );
        }
    }
}

