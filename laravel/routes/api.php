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

Route::middleware('auth:sanctum')->get('/user', fn (Request $r) => $r->user());

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);  // public list
Route::post('/products', [ProductController::class, 'store']); // owner upload
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('health', fn () => response()->json(['ok' => true]));

//Protected routes
Route::get('/whoami', function (Request $r) {
    return response()->json($r->user());
});


Route::middleware('auth:sanctum')->group(function () {
    // Cart
    Route::get('/cart',               [CartController::class, 'index']);
    Route::post('/cart',              [CartController::class, 'store']);
    Route::patch('/cart/{product}',   [CartController::class, 'update']);
    Route::delete('/cart/{product}',  [CartController::class, 'destroy']);
    Route::delete('/cart',            [CartController::class, 'clear']);

    // Orders
    Route::post('/orders/checkout',   [OrderController::class, 'checkout']);
    Route::get('/orders',             [OrderController::class, 'index']);
    Route::patch('/orders/{order}',   [OrderController::class, 'updateStatus']);


    // Profiles
    Route::get('/me/profile', [ProfileController::class, 'me']);
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




    // Owner sales
    Route::get('/owner/purchases',    [OrderController::class, 'ownerPurchases']);

    // Logout
    Route::post('/logout',            [AuthController::class, 'logout']);
});
