<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientBatchController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Api\CartController as ApiCartController;
use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TestController;
use App\Models\Product;

Route::middleware('auth:sanctum')->get('/user', fn (Request $r) => $r->user());

// Health check endpoints
Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()]));
Route::options('/health', fn () => response()->json(['status' => 'ok'])); // For CORS preflight

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/test/products', [TestController::class, 'testProducts']); // Test endpoint
Route::get('/shops/{owner}', [ProfileController::class, 'publicShop']); // public

// Notifications route (for frontend)
Route::middleware('auth:sanctum')->get('/notifications', function () {
    return response()->json([
        'data' => [],
        'unread_count' => 0
    ]);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/whoami', function (Request $r) {
        return response()->json($r->user());
    });

    // Cart routes
    Route::get('/cart', [ApiCartController::class, 'index']);
    Route::post('/cart', [ApiCartController::class, 'store']);
    Route::patch('/cart/{productId}', [ApiCartController::class, 'update']);
    Route::delete('/cart/{productId}', [ApiCartController::class, 'destroy']);
    Route::delete('/cart', [ApiCartController::class, 'clear']);

    // Order routes
    Route::get('/orders', [ApiOrderController::class, 'index']);
    Route::post('/orders/checkout', [ApiOrderController::class, 'checkout']);
    Route::patch('/orders/{id}', [ApiOrderController::class, 'updateStatus']);
    Route::get('/seller/orders', [ApiOrderController::class, 'getSellerOrders']);

    // Buyer-specific routes
    Route::get('/buyer/dashboard', [\App\Http\Controllers\Api\BuyerDashboardController::class, 'index']);
    Route::get('/buyer/orders', [\App\Http\Controllers\Api\BuyerOrderController::class, 'index']);
    Route::get('/buyer/orders/{id}', [\App\Http\Controllers\Api\BuyerOrderController::class, 'show']);
    Route::patch('/buyer/orders/{id}/cancel', [\App\Http\Controllers\Api\BuyerOrderController::class, 'cancel']);
    Route::get('/buyer/orders/stats', [\App\Http\Controllers\Api\BuyerOrderController::class, 'getStats']);
    Route::get('/buyer/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'index']);
    Route::post('/buyer/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'store']);
    Route::delete('/buyer/wishlist/{productId}', [\App\Http\Controllers\Api\WishlistController::class, 'destroy']);
    Route::get('/buyer/wishlist/check/{productId}', [\App\Http\Controllers\Api\WishlistController::class, 'check']);

    // Seller-specific routes
    Route::get('/seller/dashboard', [\App\Http\Controllers\Api\SellerDashboardController::class, 'index']);
    Route::get('/seller/stats', [\App\Http\Controllers\Api\SellerDashboardController::class, 'getStats']);
    Route::get('/seller/orders', [\App\Http\Controllers\Api\SellerOrderController::class, 'index']);
    Route::patch('/seller/orders/{orderId}', [\App\Http\Controllers\Api\SellerOrderController::class, 'updateStatus']);
    Route::get('/seller/orders/stats', [\App\Http\Controllers\Api\SellerOrderController::class, 'getStats']);

    // Seller Products Management (NEW)
    Route::get('/seller/products', [\App\Http\Controllers\Api\SellerProductController::class, 'index']);
    Route::post('/seller/products', [\App\Http\Controllers\Api\SellerProductController::class, 'store']);
    Route::get('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'show']);
    Route::put('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'update']);
    Route::delete('/seller/products/{id}', [\App\Http\Controllers\Api\SellerProductController::class, 'destroy']);
    Route::get('/seller/products/stats', [\App\Http\Controllers\Api\SellerProductController::class, 'getStats']);

    // Notifications routes (ENHANCED)
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // Shop management routes (new enhanced version)
    Route::get('/owner/shop', [\App\Http\Controllers\Api\ShopController::class, 'getShop']);
    Route::post('/owner/shop', [\App\Http\Controllers\Api\ShopController::class, 'updateShop']);
    Route::get('/owner/shop/stats', [\App\Http\Controllers\Api\ShopController::class, 'getShopStats']);
    Route::post('/owner/shop/upload', [\App\Http\Controllers\Api\ShopController::class, 'uploadImage']);

    // User Profile routes
    Route::get('/user/profile', [\App\Http\Controllers\Api\UserProfileController::class, 'show']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\UserProfileController::class, 'update']);
    Route::put('/user/password', [\App\Http\Controllers\Api\UserProfileController::class, 'updatePassword']);
    Route::get('/user/addresses', [\App\Http\Controllers\Api\UserProfileController::class, 'getAddresses']);
    Route::put('/user/addresses/{id}', [\App\Http\Controllers\Api\UserProfileController::class, 'updateAddress']);

    // Product management (for sellers)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Profile routes (user profile only)
    Route::get('/me/profile', [ProfileController::class, 'me']);
    Route::post('/me/profile', [ProfileController::class, 'updateMe']);

    // Ingredients
    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);
    Route::patch('/ingredients/{ingredient}', [IngredientController::class, 'update']);
    Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy']);

    // Ingredient consumption batches (per category & period)
    Route::get('/owner/ingredient-batches', [IngredientBatchController::class, 'index']);
    Route::post('/owner/ingredient-batches', [IngredientBatchController::class, 'store']);
    Route::delete('/owner/ingredient-batches/{batch}', [IngredientBatchController::class, 'destroy']);

    // Reports
    Route::get('/owner/profit/category', [ReportController::class, 'profitByCategory']);
    Route::get('/owner/profit/summary', [ReportController::class, 'profitSummary']);
    Route::get('/owner/dashboard', [ReportController::class, 'dashboard']);

    // Owner sales
    Route::get('/owner/purchases', [OrderController::class, 'ownerPurchases']);

    // GET /api/me/products
    Route::get('/me/products', function (Request $r) {
        $user = $r->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $perPage = (int) $r->query('per_page', 12);
        $page = (int) $r->query('page', 1);
        $q = trim((string) $r->query('q', ''));
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
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    });
});

// Legacy routes (keeping for backward compatibility)
Route::middleware('auth:sanctum')->group(function () {
    // Legacy Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{productId}', [CartController::class, 'update']);
    Route::delete('/cart/{productId}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Legacy orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::patch('/orders/{id}', [OrderController::class, 'updateStatus']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // Legacy shop profiles (keeping for compatibility)
    Route::get('/me/shop-profile', [ProfileController::class, 'myShop']);
    Route::post('/me/shop-profile', [ProfileController::class, 'updateShop']);
});
