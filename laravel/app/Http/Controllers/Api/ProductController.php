<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['owner']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Category filter - use exact match with case-insensitive comparison
        if ($request->has('category') && $request->category) {
            // Log the category filter for debugging
            \Log::info('Category filter requested:', ['category' => $request->category]);

            // Use case-insensitive exact match
            $query->whereRaw('LOWER(category) = LOWER(?)', [$request->category]);

            // Log the SQL query for debugging
            \Log::info('Category filter SQL:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        }

        // Price range filter
        if ($request->has('min_price') && $request->min_price > 0) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price > 0) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'featured');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('id', 'desc');
        }

        $products = $query->paginate(12);

        // Add review statistics to each product
        $products->getCollection()->transform(function ($product) {
            // Load reviews count and average without loading full reviews
            $reviewsCount = \DB::table('product_reviews')
                ->where('product_id', $product->id)
                ->count();

            $averageRating = \DB::table('product_reviews')
                ->where('product_id', $product->id)
                ->avg('rating');

            $product->reviews_count = $reviewsCount;
            $product->average_rating = $averageRating ? round($averageRating, 1) : 0;

            return $product;
        });

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with(['owner'])->findOrFail($id);

        // Add review statistics using direct DB queries
        $reviewsCount = \DB::table('product_reviews')
            ->where('product_id', $product->id)
            ->count();

        $averageRating = \DB::table('product_reviews')
            ->where('product_id', $product->id)
            ->avg('rating');

        $product->reviews_count = $reviewsCount;
        $product->average_rating = $averageRating ? round($averageRating, 1) : 0;

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:120',
            'image_path' => 'nullable|string'
        ]);

        $product = Product::create([
            'owner_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'image_path' => $request->image_path,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::where('owner_id', Auth::id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:120',
            'image_path' => 'nullable|string'
        ]);

        $product->update($request->all());

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::where('owner_id', Auth::id())->findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
