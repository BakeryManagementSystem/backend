<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class SellerDashboardController extends Controller
{
    /**
     * Get seller dashboard data
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get basic stats
        $totalProducts = Product::where('owner_id', $user->id)->count();

        // Get orders for this seller's products
        $sellerOrders = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        });

        $totalOrders = $sellerOrders->count();
        $pendingOrders = $sellerOrders->where('status', 'pending')->count();

        // Calculate total revenue
        $totalRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['delivered', 'shipped']);
            })->sum('line_total');

        // Get average rating (placeholder since products don't have rating field)
        $averageRating = 4.5; // Default value since rating field doesn't exist

        // Get recent orders
        $recentOrders = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->with(['orderItems' => function($query) use ($user) {
            $query->where('owner_id', $user->id)->with('product');
        }, 'buyer'])
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function($order) use ($user) {
            $sellerItems = $order->orderItems->where('owner_id', $user->id);
            $firstItem = $sellerItems->first();

            return [
                'id' => $order->id,
                'customer' => $order->buyer->name ?? 'Guest',
                'product' => $firstItem->product->name ?? 'Unknown Product',
                'amount' => (float) $sellerItems->sum('line_total'),
                'status' => $order->status,
                'date' => $order->created_at->format('Y-m-d')
            ];
        });

        // Get top products
        $topProducts = Product::where('owner_id', $user->id)
            ->withCount(['orderItems as sales_count'])
            ->withSum(['orderItems as revenue'], 'line_total')
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sales' => $product->sales_count ?? 0,
                    'revenue' => (float) ($product->revenue ?? 0),
                    'image' => $product->image_url ?? $product->image_path ?? null,
                    'rating' => 4.5, // Default since rating field doesn't exist
                    'stock' => 10 // Default since stock field doesn't exist
                ];
            });

        // Get recent activities
        $recentActivities = collect([]);

        // Add recent orders as activities
        Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->with('orderItems.product', 'buyer')
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get()
        ->each(function($order) use ($recentActivities, $user) {
            $sellerItems = $order->orderItems->where('owner_id', $user->id);
            if ($sellerItems->isNotEmpty()) {
                $recentActivities->push([
                    'id' => 'order_' . $order->id,
                    'type' => 'order',
                    'message' => 'New order received for ' . $sellerItems->first()->product->name,
                    'time' => $order->created_at->diffForHumans(),
                    'status' => $order->status
                ]);
            }
        });

        return response()->json([
            'stats' => [
                'totalProducts' => $totalProducts,
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'totalRevenue' => (float) $totalRevenue,
                'averageRating' => $averageRating
            ],
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'recentActivities' => $recentActivities->take(10)->values()
        ]);
    }

    /**
     * Get seller statistics
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        $totalProducts = Product::where('owner_id', $user->id)->count();

        $totalOrders = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })->count();

        $totalRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['delivered', 'shipped', 'processing']);
            })->sum('line_total');

        $monthlyRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['delivered', 'shipped', 'processing'])
                      ->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            })->sum('line_total');

        return response()->json([
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalRevenue' => (float) $totalRevenue,
            'monthlyRevenue' => (float) $monthlyRevenue
        ]);
    }
}
