<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of active categories.
     */
    public function index()
    {
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'image' => $category->image,
                    'status' => $category->status,
                    'sort_order' => $category->sort_order,
                    'products_count' => $category->products()->count()
                ];
            })
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        $category = Category::active()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'image' => $category->image,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'products_count' => $category->products()->count(),
                'products' => $category->products()->where('status', 'active')->get()
            ]
        ]);
    }
}

