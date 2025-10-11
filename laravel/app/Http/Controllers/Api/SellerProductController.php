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
            'category_id' => 'required|integer|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'ingredients' => 'nullable|string',
            'allergens' => 'nullable|string',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'nullable|url',
            'selected_ingredients' => 'nullable|string',
            'ingredient_cost' => 'nullable|numeric|min:0'
        ]);

        // Handle image uploads
        $imagePaths = [];
        $imageUrls = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
        }

        // Handle image URLs
        if ($request->has('image_urls')) {
            $imageUrls = is_array($request->image_urls) ? $request->image_urls : json_decode($request->image_urls, true);
        }

        // Combine all images
        $allImages = array_merge($imagePaths, $imageUrls ?? []);
        $primaryImage = !empty($allImages) ? $allImages[0] : null;

        // Get category name from category_id
        $category = \App\Models\Category::find($request->category_id);

        $product = Product::create([
            'owner_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'category' => $category ? $category->name : 'Uncategorized',
            'category_id' => $request->category_id,
            'stock_quantity' => $request->stock_quantity ?? 0,
            'sku' => $request->sku ?? 'SKU-' . time() . '-' . rand(1000, 9999),
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
            'ingredients' => $request->ingredients,
            'allergens' => $request->allergens,
            'status' => $request->status,
            'is_featured' => $request->is_featured ?? false,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'image_path' => $primaryImage,
            'images' => json_encode($allImages)
        ]);

        // Store selected ingredients if provided
        if ($request->has('selected_ingredients')) {
            $selectedIngredients = json_decode($request->selected_ingredients, true);
            // You can store this in a product_ingredients pivot table if needed
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'discount_price' => (float) ($product->discount_price ?? 0),
                'category' => $product->category,
                'category_id' => $product->category_id,
                'stock_quantity' => $product->stock_quantity,
                'sku' => $product->sku,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
                'image_url' => $product->image_url,
                'image_urls' => $product->image_urls,
                'created_at' => $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : null
            ]
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $product = Product::where('owner_id', Auth::id())->findOrFail($id);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'discount_price' => (float) ($product->discount_price ?? 0),
                'category' => $product->category,
                'stock_quantity' => (int) $product->stock_quantity,
                'sku' => $product->sku,
                'weight' => (float) ($product->weight ?? 0),
                'status' => $product->status ?? 'active',
                'is_featured' => (bool) ($product->is_featured ?? false),
                'image_path' => $product->image_path,
                'image_url' => $product->image_url ?? $product->image_path,
                'images' => $product->images ?? [],
                'owner_id' => $product->owner_id,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ]
        ]);
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
            'category_id' => 'required|integer|exists:categories,id',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:255',
            'ingredients' => 'nullable|string',
            'allergens' => 'nullable|string',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'nullable|url'
        ]);

        // Handle image uploads if any
        $imagePaths = [];
        $imageUrls = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
        }

        // Handle image URLs
        if ($request->has('image_urls')) {
            $imageUrls = is_array($request->image_urls) ? $request->image_urls : json_decode($request->image_urls, true);
        }

        // Get category name from category_id
        $category = \App\Models\Category::find($request->category_id);

        // Prepare update data
        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'discount_price' => $request->discount_price,
            'category' => $category ? $category->name : $product->category,
            'category_id' => $request->category_id,
            'stock_quantity' => $request->stock_quantity ?? $product->stock_quantity,
            'sku' => $request->sku ?? $product->sku,
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
            'ingredients' => $request->ingredients,
            'allergens' => $request->allergens,
            'status' => $request->status,
            'is_featured' => $request->is_featured ?? false,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ];

        // Update images if new ones were uploaded
        if (!empty($imagePaths) || !empty($imageUrls)) {
            $allImages = array_merge($imagePaths, $imageUrls ?? []);
            $updateData['image_path'] = !empty($allImages) ? $allImages[0] : $product->image_path;
            $updateData['images'] = json_encode($allImages);
        }

        $product->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'discount_price' => (float) ($product->discount_price ?? 0),
                'category' => $product->category,
                'category_id' => $product->category_id,
                'stock_quantity' => $product->stock_quantity,
                'sku' => $product->sku,
                'status' => $product->status,
                'is_featured' => $product->is_featured,
                'image_url' => $product->image_url,
                'image_urls' => $product->image_urls,
                'created_at' => $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : null
            ]
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
