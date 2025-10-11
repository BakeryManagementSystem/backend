<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // app/Http/Controllers/ProductController.php
     public function index(Request $request)
        {
            $perPage  = (int) $request->input('per_page', 12);
            $q        = trim((string) $request->input('q', ''));
            $category = trim((string) $request->input('category', ''));

            $query = Product::query()
                // Load owner relationship with shop profile
                ->with(['owner', 'owner.shopProfile'])
                // Select all available columns, handling both basic and enhanced database structures
                ->select(['id','owner_id','name','description','price','discount_price','category','category_id','stock_quantity','sku','weight','dimensions','ingredients','allergens','status','is_featured','meta_title','meta_description','image_path','images'])
                // filter
                ->when($q !== '', function($w) use ($q) {
                    $w->where(function($x) use ($q) {
                        $x->where('name', 'like', "%{$q}%")
                          ->orWhere('category', 'like', "%{$q}%");
                    });
                })
                ->when($category !== '', fn($w) => $w->where('category', $category))
                // Order by ID since timestamps are disabled
                ->orderBy('id','desc');

            // You can return simple list or paginator. Let's use paginator:
            $paginator = $query->paginate($perPage);

            // Map image_url from image_path and handle missing fields gracefully
            $paginator->getCollection()->transform(function ($p) {
                $p->image_url = $p->image_path
                    ? (url('/storage/'.$p->image_path))
                    : null;

                // Add image_urls for multiple images
                $p->image_urls = $p->images && is_array($p->images) ?
                    array_map(fn($path) => url('/storage/' . $path), $p->images) : [];

                // Ensure price fields are properly cast to numbers
                $p->price = (float) $p->price;
                $p->discount_price = $p->discount_price ? (float) $p->discount_price : null;

                // Add default values for fields that might not exist
                $p->status = $p->status ?? 'active';
                $p->stock_quantity = (int) ($p->stock_quantity ?? 10);
                $p->is_featured = (bool) ($p->is_featured ?? false);
                $p->ingredients = $p->ingredients ?? [];
                $p->allergens = $p->allergens ?? [];

                // Add owner information
                if ($p->owner) {
                    $p->seller = $p->owner->shop_name ?? $p->owner->name ?? 'Unknown Seller';
                    $p->seller_id = $p->owner->id;
                } else {
                    $p->seller = 'Unknown Seller';
                    $p->seller_id = null;
                }

                return $p;
            });

            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        }


    // Owner: upload/create product (supports image)
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!in_array(strtolower($user->user_type), ['owner','seller'])) {
            return response()->json(['message' => 'Only shop owners can create products'], 403);
        }

        // Validate all frontend fields
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string'],
            'price'           => ['required', 'numeric', 'min:0'],
            'discount_price'  => ['nullable', 'numeric', 'min:0'],
            'category_id'     => ['required', 'integer'],
            'stock_quantity'  => ['nullable', 'integer', 'min:0'],
            'sku'             => ['nullable', 'string', 'max:100'],
            'weight'          => ['nullable', 'numeric', 'min:0'],
            'dimensions'      => ['nullable', 'string', 'max:255'],
            'ingredients'     => ['nullable', 'string'], // JSON string from frontend
            'allergens'       => ['nullable', 'string'], // JSON string from frontend
            'status'          => ['nullable', 'in:active,draft,out_of_stock'],
            'is_featured'     => ['nullable', 'in:0,1,true,false'], // Accept string boolean values
            'meta_title'      => ['nullable', 'string', 'max:255'],
            'meta_description'=> ['nullable', 'string', 'max:500'],
            'images'          => ['nullable', 'array', 'max:5'],
            'images.*'        => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'image_urls'      => ['nullable', 'array', 'max:5'],
            'image_urls.*'    => ['url'],
        ]);

        // Handle multiple image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }
        }

        // Handle image URLs
        $imageUrls = [];
        if (!empty($data['image_urls'])) {
            $imageUrls = array_filter($data['image_urls'], function($url) {
                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            });
        }

        // Combine uploaded files and URLs for the images array
        $allImages = array_merge($imagePaths, $imageUrls);

        // Parse JSON strings for ingredients and allergens
        $ingredients = [];
        $allergens = [];

        if (!empty($data['ingredients'])) {
            $decoded = json_decode($data['ingredients'], true);
            if (is_array($decoded)) {
                $ingredients = $decoded;
            }
        }

        if (!empty($data['allergens'])) {
            $decoded = json_decode($data['allergens'], true);
            if (is_array($decoded)) {
                $allergens = $decoded;
            }
        }

        // Map category_id to category name
        $categoryMap = [
            1 => 'Bread & Rolls',
            2 => 'Pastries',
            3 => 'Cakes',
            4 => 'Cookies',
            5 => 'Muffins & Cupcakes',
            6 => 'Specialty & Dietary'
        ];

        // Create product with all enhanced fields
        $product = Product::create([
            'owner_id'        => $user->id,
            'name'            => $data['name'],
            'description'     => $data['description'],
            'price'           => $data['price'],
            'discount_price'  => $data['discount_price'] ?? null,
            'category'        => $categoryMap[$data['category_id']] ?? 'Other',
            'category_id'     => $data['category_id'],
            'stock_quantity'  => $data['stock_quantity'] ?? 0,
            'sku'             => $data['sku'] ?? null,
            'weight'          => $data['weight'] ?? null,
            'dimensions'      => $data['dimensions'] ?? null,
            'ingredients'     => $ingredients,
            'allergens'       => $allergens,
            'status'          => $data['status'] ?? 'active',
            'is_featured'     => in_array($data['is_featured'] ?? false, [true, '1', 1]),
            'meta_title'      => $data['meta_title'] ?? null,
            'meta_description'=> $data['meta_description'] ?? null,
            'image_path'      => !empty($allImages) ? $allImages[0] : null,
            'images'          => $allImages,
        ]);

        // Refresh the model to get the properly casted attributes
        $product->refresh();

        // Transform images for response
        $product->image_url = $product->image_path
            ? (str_starts_with($product->image_path, 'http') ? $product->image_path : url('/storage/'.$product->image_path))
            : null;

        $product->image_urls = array_map(function($path) {
            return str_starts_with($path, 'http') ? $path : url('/storage/' . $path);
        }, $product->images ?? []);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    // (Optional) Single product
    public function show(Product $product)
    {
        // Load owner relationship with shop profile
        $product->load(['owner', 'owner.shopProfile']);

        // Add seller information
        if ($product->owner) {
            $product->seller = $product->owner->shop_name ?? $product->owner->name ?? 'Unknown Seller';
            $product->seller_id = $product->owner->id;
        } else {
            $product->seller = 'Unknown Seller';
            $product->seller_id = null;
        }

        // Transform images for response
        $product->image_url = $product->image_path
            ? (str_starts_with($product->image_path, 'http') ? $product->image_path : url('/storage/'.$product->image_path))
            : null;

        $product->image_urls = array_map(function($path) {
            return str_starts_with($path, 'http') ? $path : url('/storage/' . $path);
        }, $product->images ?? []);

        return response()->json($product);
    }
}
