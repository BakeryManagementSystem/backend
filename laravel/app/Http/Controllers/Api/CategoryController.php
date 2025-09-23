<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;

class CategoryController extends Controller
{
    public function index()
    {
        // Return predefined categories for bakery with actual product counts
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

        // Add product counts for each category
        foreach ($categories as &$category) {
            $categoryName = $category['name'];

            // Count products that match this category (case-insensitive)
            $productCount = Product::whereRaw('LOWER(category) LIKE ?', ['%' . strtolower($this->getCategoryKeywords($categoryName)) . '%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->getCategoryKeywords($categoryName)) . '%'])
                ->count();

            $category['products_count'] = $productCount;
        }

        return response()->json($categories);
    }

    /**
     * Get search keywords for category matching
     */
    private function getCategoryKeywords($categoryName)
    {
        $keywords = [
            'Bread & Rolls' => 'bread',
            'Pastries' => 'pastry',
            'Cakes' => 'cake',
            'Cookies' => 'cookie',
            'Muffins & Cupcakes' => 'muffin',
            'Specialty & Dietary' => 'specialty'
        ];

        return $keywords[$categoryName] ?? strtolower($categoryName);
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
