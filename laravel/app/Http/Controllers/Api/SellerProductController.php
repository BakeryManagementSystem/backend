<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SellerProductController extends Controller
{
    /**
     * Display a listing of seller's products
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status');

        $query = Product::where('owner_id', $user->id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $formattedProducts = $products->getCollection()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'discount_price' => (float) ($product->discount_price ?? 0),
                'category' => $product->category,
                'stock_quantity' => $product->stock_quantity,
                'sku' => $product->sku,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
                'image_url' => $product->image_url,
                'image_urls' => $product->image_urls,
                'created_at' => $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : null
            ];
        });

        return response()->json([
            'data' => $formattedProducts,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem()
            ]
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'category' => 'required|string',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'boolean',
            'image_path' => 'nullable|string',
            'images' => 'nullable|array'
        ]);

        $product = Product::create([
            'owner_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'category' => $request->category,
            'stock_quantity' => $request->stock_quantity,
            'sku' => $request->sku ?? 'SKU' . time(),
            'weight' => $request->weight,
            'status' => $request->status,
            'is_featured' => $request->is_featured ?? false,
            'image_path' => $request->image_path,
            'images' => $request->images
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $product = Product::where('owner_id', Auth::id())->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $product = Product::where('owner_id', Auth::id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'category' => 'required|string',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'boolean',
            'image_path' => 'nullable|string',
            'images' => 'nullable|array'
        ]);

        $product->update($request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        $product = Product::where('owner_id', Auth::id())->findOrFail($id);

        // Delete associated files if they exist
        if ($product->image_path) {
            Storage::delete($product->image_path);
        }

        if ($product->images) {
            foreach ($product->images as $imagePath) {
                Storage::delete($imagePath);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get product statistics
     */
    public function getStats()
    {
        $userId = Auth::id();

        $totalProducts = Product::where('owner_id', $userId)->count();
        $activeProducts = Product::where('owner_id', $userId)->where('status', 'active')->count();
        $featuredProducts = Product::where('owner_id', $userId)->where('is_featured', true)->count();
        $lowStockProducts = Product::where('owner_id', $userId)->where('stock_quantity', '<=', 5)->count();

        return response()->json([
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'featuredProducts' => $featuredProducts,
            'lowStockProducts' => $lowStockProducts
        ]);
    }
}
