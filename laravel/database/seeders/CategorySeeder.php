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
                'name' => 'Artisan Breads',
                'description' => 'Handcrafted breads from local bakeries',
                'parent_id' => null,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Cakes & Celebration',
                'description' => 'Birthday cakes, wedding cakes, and special occasion desserts',
                'parent_id' => null,
                'status' => true,
                'sort_order' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Pastries & Croissants',
                'description' => 'Flaky pastries and French-style baked goods',
                'parent_id' => null,
                'status' => true,
                'sort_order' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Cookies & Biscuits',
                'description' => 'Fresh cookies, biscuits, and bite-sized treats',
                'parent_id' => null,
                'status' => true,
                'sort_order' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Custom Orders',
                'description' => 'Made-to-order cakes, personalized gifts, and special requests',
                'parent_id' => null,
                'status' => true,
                'sort_order' => 5,
            ],
            [
                'id' => 6,
                'name' => 'Bakery Bundles',
                'description' => 'Gift boxes, hampers, and curated bakery collections',
                'parent_id' => null,
                'status' => true,
                'sort_order' => 6,
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
