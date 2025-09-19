<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index()
    {
        // Return predefined categories for bakery
        $categories = [
            [
                'id' => 1,
                'name' => 'Bread & Rolls',
                'slug' => 'bread-rolls',
                'description' => 'Fresh baked breads and rolls'
            ],
            [
                'id' => 2,
                'name' => 'Pastries',
                'slug' => 'pastries',
                'description' => 'Delicious pastries and croissants'
            ],
            [
                'id' => 3,
                'name' => 'Cakes',
                'slug' => 'cakes',
                'description' => 'Custom and ready-made cakes'
            ],
            [
                'id' => 4,
                'name' => 'Cookies',
                'slug' => 'cookies',
                'description' => 'Fresh baked cookies and biscuits'
            ],
            [
                'id' => 5,
                'name' => 'Muffins & Cupcakes',
                'slug' => 'muffins-cupcakes',
                'description' => 'Sweet muffins and decorated cupcakes'
            ],
            [
                'id' => 6,
                'name' => 'Specialty & Dietary',
                'slug' => 'specialty',
                'description' => 'Gluten-free, vegan, and specialty items'
            ]
        ];

        return response()->json($categories);
    }

    public function show($id)
    {
        $categories = [
            1 => ['id' => 1, 'name' => 'Bread & Rolls', 'slug' => 'bread-rolls'],
            2 => ['id' => 2, 'name' => 'Pastries', 'slug' => 'pastries'],
            3 => ['id' => 3, 'name' => 'Cakes', 'slug' => 'cakes'],
            4 => ['id' => 4, 'name' => 'Cookies', 'slug' => 'cookies'],
            5 => ['id' => 5, 'name' => 'Muffins & Cupcakes', 'slug' => 'muffins-cupcakes'],
            6 => ['id' => 6, 'name' => 'Specialty & Dietary', 'slug' => 'specialty']
        ];

        $category = $categories[$id] ?? null;

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }
}
