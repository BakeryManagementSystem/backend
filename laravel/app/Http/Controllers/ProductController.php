<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Buyer: list products (with simple pagination)
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $products = Product::orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    // Owner: upload/create product (supports image)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'category'    => ['nullable', 'string', 'max:120'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB
            'owner_id'    => ['nullable', 'integer'],
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            // stores under storage/app/public/products and is served via /storage symlink
            $path = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'owner_id'    => $data['owner_id'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'category'    => $data['category'] ?? null,
            'image_path'  => $path,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    // (Optional) Single product
    public function show(Product $product)
    {
        return response()->json($product);
    }
}
