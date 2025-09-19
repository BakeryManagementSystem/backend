<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuyerDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Get user statistics
        $stats = $this->getUserStats($userId);

        // Get recent orders
        $recentOrders = $this->getRecentOrders($userId);

        // Get wishlist items
        $wishlistItems = $this->getWishlistItems($userId);

        // Get recommended products
        $recommendedProducts = $this->getRecommendedProducts($userId);

        return response()->json([
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'wishlistItems' => $wishlistItems,
            'recommendedProducts' => $recommendedProducts
        ]);
    }

    private function getUserStats($userId)
    {
        $totalOrders = Order::where('buyer_id', $userId)->count();
        $pendingOrders = Order::where('buyer_id', $userId)->where('status', 'pending')->count();
        $totalSpent = Order::where('buyer_id', $userId)->sum('total_amount');
        $wishlistCount = Wishlist::where('user_id', $userId)->count();

        return [
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'totalSpent' => (float) $totalSpent,
            'wishlistItems' => $wishlistCount
        ];
    }

    private function getRecentOrders($userId)
    {
        return Order::with(['orderItems.product'])
            ->where('buyer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'date' => $order->created_at->format('Y-m-d'),
                    'total' => (float) $order->total_amount,
                    'status' => $order->status,
                    'items' => $order->orderItems->count(),
                    'products' => $order->orderItems->map(function ($item) {
                        return [
                            'name' => $item->product->name,
                            'image' => $item->product->image_path,
                            'quantity' => $item->quantity,
                            'price' => (float) $item->unit_price
                        ];
                    })
                ];
            });
    }

    private function getWishlistItems($userId)
    {
        return Wishlist::with(['product.owner'])
            ->where('user_id', $userId)
            ->limit(6)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => (float) $item->product->price,
                    'image' => $item->product->image_path,
                    'inStock' => true, // You can add stock tracking later
                    'seller' => $item->product->owner->name ?? 'Unknown'
                ];
            });
    }

    private function getRecommendedProducts($userId)
    {
        // Get products based on user's order history and popular items
        $userCategories = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.buyer_id', $userId)
            ->pluck('products.category')
            ->unique()
            ->filter();

        $query = Product::with(['owner']);

        if ($userCategories->isNotEmpty()) {
            $query->whereIn('category', $userCategories);
        }

        return $query->where('owner_id', '!=', $userId) // Don't recommend own products
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($product) {
                // Calculate average rating
                $averageRating = ProductReview::where('product_id', $product->id)->avg('rating') ?? 0;
                $reviewCount = ProductReview::where('product_id', $product->id)->count();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'image' => $product->image_path,
                    'rating' => round($averageRating, 1),
                    'reviewCount' => $reviewCount,
                    'seller' => $product->owner->name ?? 'Unknown'
                ];
            });
    }
}
