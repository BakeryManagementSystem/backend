<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Test endpoint to verify database and products are working
     */
    public function testProducts()
    {
        try {
            // Get all products with their basic info
            $products = Product::select(['id', 'name', 'price', 'category', 'owner_id'])
                              ->limit(10)
                              ->get();

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful',
                'total_products' => Product::count(),
                'sample_products' => $products,
                'database_columns' => \Schema::getColumnListing('products')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Database connection failed'
            ], 500);
        }
    }
}
