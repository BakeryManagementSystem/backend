<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\IngredientBatch;
use App\Models\IngredientBatchItem;
use App\Models\OrderIngredientCost;
use Illuminate\Support\Facades\DB;

class SellerDashboardController extends Controller
{
    /**
     * Get seller dashboard data
     */
    public function index(Request $request)
    {
        $user = $request->user();

        \Log::info("=== SELLER DASHBOARD REQUEST START ===");
        \Log::info("User ID: {$user->id}, Name: {$user->name}");

        // Get basic stats
        $totalProducts = Product::where('owner_id', $user->id)->count();
        \Log::info("Total products for user {$user->id}: {$totalProducts}");

        // Get orders for this seller's products
        $sellerOrders = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        });

        $totalOrders = $sellerOrders->count();
        $pendingOrders = $sellerOrders->where('status', 'pending')->count();
        \Log::info("Total orders: {$totalOrders}, Pending: {$pendingOrders}");

        // Calculate total revenue
        $totalRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['delivered', 'shipped']);
            })->sum('line_total');
        \Log::info("Total revenue calculation: {$totalRevenue}");

        // Get average rating (placeholder since products don't have rating field)
        $averageRating = 4.5; // Default value since rating field doesn't exist

        // Calculate ingredient investment/costs (including both manual batches and order-based costs)
        $manualIngredientInvestment = IngredientBatch::where('owner_id', $user->id)
            ->sum('total_cost');
        \Log::info("Manual ingredient investment: {$manualIngredientInvestment}");

        // Check if OrderIngredientCost records exist
        $orderCostRecords = OrderIngredientCost::where('owner_id', $user->id)->get();
        \Log::info("OrderIngredientCost records found: " . $orderCostRecords->count());

        foreach ($orderCostRecords as $record) {
            \Log::info("Record ID: {$record->id}, Order: {$record->order_id}, Product: {$record->product_name}, Cost: {$record->total_ingredient_cost}");
        }

        $orderBasedIngredientCosts = OrderIngredientCost::where('owner_id', $user->id)
            ->sum('total_ingredient_cost');

        \Log::info("Dashboard investment calculation for user {$user->id}: Manual batches: {$manualIngredientInvestment}, Order-based: {$orderBasedIngredientCosts}");

        $totalIngredientInvestment = $manualIngredientInvestment + $orderBasedIngredientCosts;
        \Log::info("Total investment: {$totalIngredientInvestment}");

        $monthlyIngredientCost = IngredientBatch::where('owner_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_cost');

        $monthlyOrderBasedCosts = OrderIngredientCost::where('owner_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_ingredient_cost');

        $monthlyIngredientCost += $monthlyOrderBasedCosts;

        // Get low stock items count (using correct column names)
        $lowStockItems = Product::where('owner_id', $user->id)
            ->where(function($query) {
                $query->where('stock_quantity', '<', 10)
                      ->orWhere('status', '!=', 'active');
            })
            ->count();

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
                'averageRating' => $averageRating,
                'totalIngredientInvestment' => (float) $totalIngredientInvestment,
                'monthlyIngredientCost' => (float) $monthlyIngredientCost,
                'lowStockItems' => $lowStockItems
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
