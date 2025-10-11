<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => 1,
                'name' => 'Bread & Rolls',
                'description' => 'Fresh baked breads, rolls, and artisan loaves',
                'status' => true,
                'sort_order' => 1
            ],
            [
                'id' => 2,
                'name' => 'Pastries',
                'description' => 'Delicious pastries, croissants, and breakfast items',
                'status' => true,
                'sort_order' => 2
            ],
            [
                'id' => 3,
                'name' => 'Cakes',
                'description' => 'Custom and ready-made cakes for all occasions',
                'status' => true,
                'sort_order' => 3
            ],
            [
                'id' => 4,
                'name' => 'Cookies',
                'description' => 'Homemade cookies and biscuits',
                'status' => true,
                'sort_order' => 4
            ],
            [
                'id' => 5,
                'name' => 'Muffins & Cupcakes',
                'description' => 'Fresh muffins and decorated cupcakes',
                'status' => true,
                'sort_order' => 5
            ],
            [
                'id' => 6,
                'name' => 'Specialty & Dietary',
                'description' => 'Gluten-free, vegan, and other specialty items',
                'status' => true,
                'sort_order' => 6
            ]
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['id' => $category['id']],
                $category
            );
        }
    }
}
