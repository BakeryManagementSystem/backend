<?php

namespace Database\Seeders;

use App\Models\IngredientBatch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class IngredientBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owners = User::where('user_type', 'owner')->get();

        if ($owners->isEmpty()) {
            $this->command->info('No owners found. Skipping ingredient batches seeding.');
            return;
        }

        $categories = ['Flour', 'Sugar', 'Dairy', 'Eggs', 'Butter', 'Yeast', 'Chocolate', 'Nuts'];

        foreach ($owners as $owner) {
            // Create 3-5 batches per owner
            $batchCount = rand(3, 5);

            for ($i = 0; $i < $batchCount; $i++) {
                $startDate = Carbon::now()->subDays(rand(1, 60));
                $endDate = (clone $startDate)->addDays(rand(7, 30));

                IngredientBatch::create([
                    'owner_id' => $owner->id,
                    'category' => $categories[array_rand($categories)],
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'notes' => 'Batch for ' . $categories[array_rand($categories)],
                    'total_cost' => rand(100, 1000) + (rand(0, 99) / 100),
                ]);
            }
        }

        $this->command->info('Ingredient batches seeded successfully!');
    }
}

