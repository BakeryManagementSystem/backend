<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SellerAnalyticsController extends Controller
{
    /**
     * Get comprehensive seller analytics
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', '30'); // days
        $timeframe = $request->get('timeframe', 'days'); // days, weeks, months

        return response()->json([
            'revenue_analytics' => $this->getRevenueAnalytics($user->id, $period, $timeframe),
            'product_analytics' => $this->getProductAnalytics($user->id, $period),
            'order_analytics' => $this->getOrderAnalytics($user->id, $period),
            'customer_analytics' => $this->getCustomerAnalytics($user->id, $period),
            'category_performance' => $this->getCategoryPerformance($user->id, $period),
            'top_products' => $this->getTopProducts($user->id, $period),
            'recent_activity' => $this->getRecentActivity($user->id)
        ]);
    }

    private function getRevenueAnalytics($userId, $period, $timeframe)
    {
        $startDate = match($timeframe) {
            'weeks' => now()->subWeeks($period),
            'months' => now()->subMonths($period),
            default => now()->subDays($period)
        };

        $groupBy = match($timeframe) {
            'weeks' => DB::raw('YEARWEEK(created_at) as period'),
            'months' => DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
            default => DB::raw('DATE(created_at) as period')
        };

        $revenueData = OrderItem::where('owner_id', $userId)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped']);
            })
            ->where('created_at', '>=', $startDate)
            ->select(
                $groupBy,
                DB::raw('SUM(line_total) as revenue'),
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('COUNT(DISTINCT order_id) as orders_count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $totalRevenue = $revenueData->sum('revenue');
        $totalUnits = $revenueData->sum('units_sold');
        $totalOrders = $revenueData->sum('orders_count');

        // Calculate growth rate
        $previousPeriodRevenue = OrderItem::where('owner_id', $userId)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped']);
            })
            ->where('created_at', '>=', $startDate->copy()->sub($period, $timeframe))
            ->where('created_at', '<', $startDate)
            ->sum('line_total');

        $growthRate = $previousPeriodRevenue > 0
            ? (($totalRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100
            : 0;

        return [
            'chart_data' => $revenueData,
            'total_revenue' => round($totalRevenue, 2),
            'total_units' => $totalUnits,
            'total_orders' => $totalOrders,
            'growth_rate' => round($growthRate, 2),
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0
        ];
    }

    private function getProductAnalytics($userId, $period)
    {
        $totalProducts = Product::where('owner_id', $userId)->count();

        return [
            'total_products' => $totalProducts,
            'active_products' => $totalProducts, // All products are active since no status field
            'low_stock_products' => 0, // No stock field
            'out_of_stock_products' => 0, // No stock field
            'new_products' => Product::where('owner_id', $userId)
                ->where('created_at', '>=', now()->subDays($period))
                ->count(),
            'stock_percentage' => 100 // All products in stock since no stock field
        ];
    }

    private function getOrderAnalytics($userId, $period)
    {
        $startDate = now()->subDays($period);

        $orders = Order::whereHas('orderItems', function($q) use ($userId) {
            $q->where('owner_id', $userId);
        })->where('created_at', '>=', $startDate);

        $totalOrders = $orders->count();
        $pendingOrders = $orders->where('status', 'pending')->count();
        $processingOrders = $orders->where('status', 'processing')->count();
        $shippedOrders = $orders->where('status', 'shipped')->count();
        $deliveredOrders = $orders->where('status', 'delivered')->count();
        $cancelledOrders = $orders->where('status', 'cancelled')->count();

        return [
            'total_orders' => $totalOrders,
            'pending' => $pendingOrders,
            'processing' => $processingOrders,
            'shipped' => $shippedOrders,
            'delivered' => $deliveredOrders,
            'cancelled' => $cancelledOrders,
            'completion_rate' => $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0
        ];
    }

    private function getCustomerAnalytics($userId, $period)
    {
        $startDate = now()->subDays($period);

        // Unique customers
        $uniqueCustomers = Order::whereHas('orderItems', function($q) use ($userId) {
            $q->where('owner_id', $userId);
        })
        ->where('created_at', '>=', $startDate)
        ->distinct('buyer_id')
        ->count('buyer_id');

        // Repeat customers
        $repeatCustomers = Order::whereHas('orderItems', function($q) use ($userId) {
            $q->where('owner_id', $userId);
        })
        ->where('created_at', '>=', $startDate)
        ->select('buyer_id', DB::raw('COUNT(*) as order_count'))
        ->groupBy('buyer_id')
        ->having('order_count', '>', 1)
        ->count();

        return [
            'unique_customers' => $uniqueCustomers,
            'repeat_customers' => $repeatCustomers,
            'retention_rate' => $uniqueCustomers > 0 ? round(($repeatCustomers / $uniqueCustomers) * 100, 1) : 0
        ];
    }

    private function getCategoryPerformance($userId, $period)
    {
        $startDate = now()->subDays($period);

        return Product::where('owner_id', $userId)
            ->select('category')
            ->withCount(['orderItems as sales_count' => function($q) use ($startDate) {
                $q->whereHas('order', function($orderQuery) use ($startDate) {
                    $orderQuery->where('created_at', '>=', $startDate)
                             ->whereIn('status', ['delivered', 'shipped']);
                });
            }])
            ->withSum(['orderItems as revenue' => function($q) use ($startDate) {
                $q->whereHas('order', function($orderQuery) use ($startDate) {
                    $orderQuery->where('created_at', '>=', $startDate)
                             ->whereIn('status', ['delivered', 'shipped']);
                });
            }], 'line_total')
            ->groupBy('category')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'category' => $item->category,
                    'sales_count' => $item->sales_count ?? 0,
                    'revenue' => $item->revenue ?? 0
                ];
            });
    }

    private function getTopProducts($userId, $period)
    {
        $startDate = now()->subDays($period);

        return Product::where('owner_id', $userId)
            ->withCount(['orderItems as sales_count' => function($q) use ($startDate) {
                $q->whereHas('order', function($orderQuery) use ($startDate) {
                    $orderQuery->where('created_at', '>=', $startDate)
                             ->whereIn('status', ['delivered', 'shipped']);
                });
            }])
            ->withSum(['orderItems as revenue' => function($q) use ($startDate) {
                $q->whereHas('order', function($orderQuery) use ($startDate) {
                    $orderQuery->where('created_at', '>=', $startDate)
                             ->whereIn('status', ['delivered', 'shipped']);
                });
            }], 'line_total')
            ->orderBy('sales_count', 'desc')
            ->take(10)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sales_count' => $product->sales_count ?? 0,
                    'revenue' => $product->revenue ?? 0,
                    'image' => $product->image_url,
                    'price' => $product->price,
                    'stock' => 10 // Default since no stock field
                ];
            });
    }

    private function getRecentActivity($userId)
    {
        $activities = collect();

        // Recent orders
        Order::whereHas('orderItems', function($q) use ($userId) {
            $q->where('owner_id', $userId);
        })
        ->with('orderItems.product', 'buyer')
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->each(function($order) use ($activities, $userId) {
            $sellerItems = $order->orderItems->where('owner_id', $userId);
            if ($sellerItems->isNotEmpty()) {
                $activities->push([
                    'type' => 'order',
                    'message' => 'New order from ' . ($order->buyer->name ?? 'Guest'),
                    'time' => $order->created_at->diffForHumans(),
                    'data' => [
                        'order_id' => $order->id,
                        'amount' => $sellerItems->sum('line_total')
                    ]
                ]);
            }
        });

        return $activities->sortByDesc('time')->take(10)->values();
    }
}
