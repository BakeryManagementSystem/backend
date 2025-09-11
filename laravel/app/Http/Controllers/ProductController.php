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
                // select only what you need; make sure these columns exist
                ->select(['id','owner_id','name','description','price','category','image_path'])
                // filter
                ->when($q !== '', function($w) use ($q) {
                    $w->where(function($x) use ($q) {
                        $x->where('name', 'like', "%{$q}%")
                          ->orWhere('category', 'like', "%{$q}%");
                    });
                })
                ->when($category !== '', fn($w) => $w->where('category', $category))
                // DO NOT order by created_at if your table doesn’t have timestamps
                ->orderBy('id','desc');

            // You can return simple list or paginator. Let’s use paginator:
            $paginator = $query->paginate($perPage);

            // Map image_url from image_path if you don’t have an accessor
            $paginator->getCollection()->transform(function ($p) {
                $p->image_url = $p->image_path
                    ? (url('/storage/'.$p->image_path))
                    : null;
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

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'category'    => ['nullable', 'string', 'max:120'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB
            'owner_id'    => ['nullable', 'integer'],
        ]);

                $path = $request->hasFile('image')
                       ? $request->file('image')->store('products','public')
                       : null;

        $product = Product::create([
            'owner_id'    => $user->id,
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
