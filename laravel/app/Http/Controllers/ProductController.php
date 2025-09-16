<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Query params:
     *  - per_page: int (default 12)
     *  - q: string (searches name, category, description; case-insensitive)
     *  - category: string (exact match)
     */
    public function index(Request $request)
    {
        $perPage  = max(1, (int) $request->input('per_page', 12));
        $q        = trim((string) $request->input('q', ''));
        $category = trim((string) $request->input('category', ''));

        $query = Product::query()
            ->select(['id','owner_id','name','description','price','category','image_path'])
            ->when($q !== '', function($w) use ($q) {
                $w->where(function($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
                      ->orWhere('category', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->when($category !== '', fn($w) => $w->where('category', $category))
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage);

        // Map image_url and normalize price to float for the response
        $paginator->getCollection()->transform(function ($p) {
            $p->price = (float) $p->price;
            $p->image_url = $p->image_path ? url('/storage/'.$p->image_path) : null;
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

    /**
     * POST /api/products
     * (Owner-only) Create a product; supports optional image upload.
     * Body:
     *  - name (string, required)
     *  - description (string, optional)
     *  - price (numeric, required)
     *  - category (string, optional)
     *  - image (file, optional; jpeg/png/jpg/gif/webp; <= 5MB)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!in_array(strtolower($user->user_type), ['owner','seller'], true)) {
            return response()->json(['message' => 'Only shop owners can create products'], 403);
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'category'    => ['nullable', 'string', 'max:120'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB
        ]);

        $path = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        $product = Product::create([
            'owner_id'    => $user->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'category'    => $data['category'] ?? null,
            'image_path'  => $path,
        ]);

        // Add a computed image_url + numeric price to the response
        $product->price = (float) $product->price;
        $product->image_url = $product->image_path ? url('/storage/'.$product->image_path) : null;

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    /**
     * GET /api/products/{product}
     * Returns a single product.
     */
    public function show(Product $product)
    {
        $product->price = (float) $product->price;
        $product->image_url = $product->image_path ? url('/storage/'.$product->image_path) : null;

        return response()->json($product);
    }
}
