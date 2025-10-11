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
                'name' => 'Breads',
                'description' => 'Fresh baked breads',
                'parent_id' => null,
                'status' => true,
            ],
            [
                'id' => 2,
                'name' => 'Pastries',
                'description' => 'Sweet and savory pastries',
                'parent_id' => null,
                'status' => true,
            ],
            [
                'id' => 3,
                'name' => 'Cakes',
                'description' => 'Custom and ready-made cakes',
                'parent_id' => null,
                'status' => true,
            ],
            [
                'id' => 4,
                'name' => 'Cookies',
                'description' => 'Freshly baked cookies',
                'parent_id' => null,
                'status' => true,
            ],
            [
                'id' => 5,
                'name' => 'Desserts',
                'description' => 'Various desserts and sweets',
                'parent_id' => null,
                'status' => true,
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
