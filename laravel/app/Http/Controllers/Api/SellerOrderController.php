<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderIngredientCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerOrderController extends Controller
{
    /**
     * Get orders for seller's products
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $search = $request->get('search');

        $query = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
        ->with(['orderItems' => function($q) use ($user) {
            $q->where('owner_id', $user->id)->with('product');
        }, 'buyer']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('buyer', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $formattedOrders = $orders->getCollection()->map(function($order) use ($user) {
            $sellerItems = $order->orderItems->where('owner_id', $user->id);

            return [
                'id' => $order->id,
                'customer' => [
                    'id' => $order->buyer->id ?? null,
                    'name' => $order->buyer->name ?? 'Guest',
                    'email' => $order->buyer->email ?? 'N/A'
                ],
                'items' => $sellerItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_image' => $item->product->image_url,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'total' => $item->line_total
                    ];
                }),
                'total_amount' => $sellerItems->sum('line_total'),
                'status' => $order->status,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                'delivery_address' => null, // No delivery address field in your schema
                'notes' => null // No notes field in your schema
            ];
        });

        return response()->json([
            'data' => $formattedOrders,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ]
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderId)
    {
        $user = $request->user();

        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,completed,cancelled'
        ]);

        $order = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->with('buyer')->findOrFail($orderId);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        $order->status = $newStatus;
        $order->save();

        // Handle financial tracking when order is delivered
        if ($newStatus === 'delivered' && $oldStatus !== 'delivered') {
            $this->updateRevenueTracking($order, $user);
        }

        // Create notification for buyer when status changes
        if ($oldStatus !== $newStatus && $order->buyer) {
            \App\Models\Notification::createOrderStatusNotification(
                $order->buyer->id,
                $order->id,
                $newStatus,
                $user->name
            );
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Update revenue tracking when order is delivered
     */
    private function updateRevenueTracking($order, $seller)
    {
        // Calculate total revenue for this seller from this order
        $sellerRevenue = $order->orderItems()
            ->where('owner_id', $seller->id)
            ->sum('line_total');

        // Log the revenue update for debugging
        \Log::info("Revenue update for seller {$seller->id}: Order {$order->id} delivered with revenue: {$sellerRevenue}");

        // Calculate and record ingredient costs for this order
        $this->recordIngredientInvestment($order, $seller);

        // Update product sales counts
        $order->orderItems()->where('owner_id', $seller->id)->each(function($item) {
            $product = $item->product;
            if ($product) {
                // Decrease stock quantity if tracking is enabled
                if ($product->stock_quantity > 0) {
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }
        });
    }

    /**
     * Record ingredient investment costs when order is delivered
     */
    private function recordIngredientInvestment($order, $seller)
    {
        \Log::info("Starting ingredient investment recording for Order {$order->id}, Seller {$seller->id}");

        $order->orderItems()->where('owner_id', $seller->id)->each(function($item) use ($order, $seller) {
            $product = $item->product;
            if ($product) {
                // Calculate ingredient cost per unit for this product
                $ingredientCostPerUnit = $this->calculateIngredientCostPerUnit($product);
                $totalIngredientCost = $ingredientCostPerUnit * $item->quantity;

                \Log::info("Processing product: {$product->name}, Price: {$product->price}, Quantity: {$item->quantity}, Cost per unit: {$ingredientCostPerUnit}, Total cost: {$totalIngredientCost}");

                try {
                    // Record the ingredient investment for this order
                    $ingredientCost = OrderIngredientCost::create([
                        'order_id' => $order->id,
                        'owner_id' => $seller->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity_sold' => $item->quantity,
                        'ingredient_cost_per_unit' => $ingredientCostPerUnit,
                        'total_ingredient_cost' => $totalIngredientCost
                    ]);

                    \Log::info("Ingredient investment successfully recorded with ID: {$ingredientCost->id}");
                } catch (\Exception $e) {
                    \Log::error("Failed to record ingredient investment: " . $e->getMessage());
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                }
            } else {
                \Log::warning("Product not found for order item ID: {$item->id}");
            }
        });

        \Log::info("Finished ingredient investment recording for Order {$order->id}");
    }

    /**
     * Calculate ingredient cost per unit for a product
     * This is a simplified calculation - you can enhance this based on your business logic
     */
    private function calculateIngredientCostPerUnit($product)
    {
        // Option 1: Use a fixed percentage of the product price (e.g., 30% of selling price)
        $costPercentage = 0.30; // 30% of selling price
        $estimatedCost = $product->price * $costPercentage;

        // Option 2: If you have actual ingredient data, you can calculate real costs
        // This would require a more complex system linking products to ingredients

        // For now, using the percentage method as a reasonable estimate
        return round($estimatedCost, 2);
    }

    /**
     * Get order statistics
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        $totalOrders = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->count();

        $pendingOrders = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->where('status', 'pending')->count();

        $processingOrders = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->where('status', 'processing')->count();

        $shippedOrders = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->where('status', 'shipped')->count();

        $deliveredOrders = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->where('status', 'delivered')->count();

        // Calculate total revenue from delivered orders
        $totalRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['delivered', 'shipped']);
            })->sum('line_total');

        // Calculate average order value
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'pending' => $pendingOrders,
                'processing' => $processingOrders,
                'shipped' => $shippedOrders,
                'delivered' => $deliveredOrders,
                'total_revenue' => (float) $totalRevenue,
                'average_order_value' => (float) $averageOrderValue,
                'daily_sales' => [],
                'monthly_sales' => [],
                'sales_by_category' => []
            ]
        ]);
    }

    /**
     * Test endpoint to manually record ingredient costs for debugging
     */
    public function testIngredientRecording(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->with('orderItems.product')->findOrFail($orderId);

        \Log::info("Manual test - recording ingredient costs for Order {$orderId}");

        $this->recordIngredientInvestment($order, $user);

        // Check if records were created
        $recordsCount = OrderIngredientCost::where('order_id', $orderId)->where('owner_id', $user->id)->count();
        $totalCost = OrderIngredientCost::where('order_id', $orderId)->where('owner_id', $user->id)->sum('total_ingredient_cost');

        return response()->json([
            'message' => 'Test completed',
            'order_id' => $orderId,
            'records_created' => $recordsCount,
            'total_cost_recorded' => $totalCost,
            'check_logs' => 'Check Laravel logs for detailed information'
        ]);
    }
}
