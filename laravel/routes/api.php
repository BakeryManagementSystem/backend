<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;    // cart
use App\Http\Controllers\OrderController;   //
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientBatchController;
use App\Http\Controllers\ReportController;
use App\Models\Product;

Route::middleware('auth:sanctum')->get('/user', fn (Request $r) => $r->user());

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);  // public list
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('health', fn () => response()->json(['ok' => true]));

//Protected routes
Route::get('/whoami', function (Request $r) {
    return response()->json($r->user());
});


Route::middleware('auth:sanctum')->group(function ()
{
    // Cart
    Route::get('/cart',               [CartController::class, 'index']);
    Route::post('/cart',              [CartController::class, 'store']);
    Route::patch('/cart/{product}',   [CartController::class, 'update']);
    Route::delete('/cart/{product}',  [CartController::class, 'destroy']);
    Route::delete('/cart',            [CartController::class, 'clear']);
    Route::post('/products',          [ProductController::class, 'store']);
    // Orders
    Route::post('/orders/checkout',   [OrderController::class, 'checkout']);
    Route::get('/orders',             [OrderController::class, 'index']);
    Route::patch('/orders/{order}',   [OrderController::class, 'updateStatus']);


    // Profiles
    Route::get('/me/profile', [ProfileController::class, 'me']);
    Route::get('/me/shop-profile',  [ProfileController::class, 'myShop']);
    Route::post('/me/shop-profile', [ProfileController::class, 'updateShop']);
    Route::post('/me/profile', [ProfileController::class, 'updateMe']);
    Route::get('/owner/shop', [ProfileController::class, 'myShop']);
    Route::post('/owner/shop', [ProfileController::class, 'updateShop']);


    // Ingredients
    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);
    Route::patch('/ingredients/{ingredient}', [IngredientController::class, 'update']);
    Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy']);


    // Ingredient consumption batches (per category & period)
        Route::get('/owner/ingredient-batches',            [IngredientBatchController::class, 'index']);
        Route::post('/owner/ingredient-batches',           [IngredientBatchController::class, 'store']);
        Route::delete('/owner/ingredient-batches/{batch}', [IngredientBatchController::class, 'destroy']);

        // Reports
        Route::get('/owner/profit/category', [ReportController::class, 'profitByCategory']);
        Route::get('/owner/profit/summary',  [ReportController::class, 'profitSummary']);
        Route::get('/owner/dashboard', [ReportController::class, 'dashboard']);




    // Owner sales
    Route::get('/owner/purchases',    [OrderController::class, 'ownerPurchases']);

    // Logout
    Route::post('/logout',            [AuthController::class, 'logout']);
});

// routes/api.php
Route::get('/shops/{owner}', [\App\Http\Controllers\ProfileController::class, 'publicShop']); // public


// GET /api/me/products

Route::middleware('auth:sanctum')->get('/me/products', function (Request $r) {
    $user = $r->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $perPage  = (int) $r->query('per_page', 12);
    $page     = (int) $r->query('page', 1);
    $q        = trim((string) $r->query('q', ''));
    $category = trim((string) $r->query('category', ''));

    $query = Product::where('owner_id', $user->id);

    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('name', 'like', "%{$q}%")
              ->orWhere('category', 'like', "%{$q}%");
        });
    }
    if ($category !== '') {
        $query->where('category', $category);
    }

    $products = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data' => $products->items(),
        'meta' => [
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'total'        => $products->total(),
        ],
    ]);
});

