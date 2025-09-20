<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerOrderController extends Controller
{
    /**
     * Display a listing of buyer's orders
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $search = $request->get('search');

        $query = Order::where('buyer_id', $user->id)
            ->with(['orderItems.product.owner']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('orderItems.product', function($productQuery) use ($search) {
                      $productQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $formattedOrders = $orders->getCollection()->map(function($order) {
            return [
                'id' => $order->id,
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                'items' => $order->orderItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown Product',
                        'product_image' => $item->product->image_url ?? $item->product->image_path ?? null,
                        'seller_name' => $item->product->owner->name ?? 'Unknown Seller',
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'line_total' => (float) $item->line_total
                    ];
                }),
                'items_count' => $order->orderItems->count(),
                'sellers' => $order->orderItems->map(function($item) {
                    return $item->product->owner->name ?? 'Unknown Seller';
                })->unique()->values()
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
     * Display the specified order
     */
    public function show($id)
    {
        $order = Order::where('buyer_id', Auth::id())
            ->with(['orderItems.product.owner'])
            ->findOrFail($id);

        return response()->json([
            'id' => $order->id,
            'total_amount' => (float) $order->total_amount,
            'status' => $order->status,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            'items' => $order->orderItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'product_image' => $item->product->image_url ?? $item->product->image_path ?? null,
                    'product_description' => $item->product->description ?? '',
                    'seller_name' => $item->product->owner->name ?? 'Unknown Seller',
                    'seller_email' => $item->product->owner->email ?? '',
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total
                ];
            }),
            'tracking_info' => [
                'status' => $order->status,
                'status_history' => $this->getStatusHistory($order)
            ]
        ]);
    }

    /**
     * Cancel an order (only if pending)
     */
    public function cancel($id)
    {
        $order = Order::where('buyer_id', Auth::id())->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'error' => 'Only pending orders can be cancelled'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        // Notify sellers about cancellation
        $sellers = $order->orderItems->pluck('owner_id')->unique();
        $buyer = Auth::user();

        foreach ($sellers as $sellerId) {
            \App\Models\Notification::create([
                'user_id' => $sellerId,
                'type' => 'order_cancelled',
                'title' => 'Order Cancelled',
                'message' => "Order #{$order->id} has been cancelled by {$buyer->name}",
                'data' => [
                    'order_id' => $order->id,
                    'buyer_name' => $buyer->name
                ]
            ]);
        }

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }

    /**
     * Get order statistics for buyer
     */
    public function getStats()
    {
        $userId = Auth::id();

        $totalOrders = Order::where('buyer_id', $userId)->count();
        $pendingOrders = Order::where('buyer_id', $userId)->where('status', 'pending')->count();
        $processingOrders = Order::where('buyer_id', $userId)->where('status', 'processing')->count();
        $shippedOrders = Order::where('buyer_id', $userId)->where('status', 'shipped')->count();
        $deliveredOrders = Order::where('buyer_id', $userId)->where('status', 'delivered')->count();
        $cancelledOrders = Order::where('buyer_id', $userId)->where('status', 'cancelled')->count();
        $totalSpent = Order::where('buyer_id', $userId)->whereIn('status', ['delivered', 'shipped'])->sum('total_amount');

        return response()->json([
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'shippedOrders' => $shippedOrders,
            'deliveredOrders' => $deliveredOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalSpent' => (float) $totalSpent
        ]);
    }

    /**
     * Get status history for tracking
     */
    private function getStatusHistory($order)
    {
        $statusFlow = ['pending', 'processing', 'shipped', 'delivered'];
        $history = [];

        foreach ($statusFlow as $status) {
            $isCompleted = array_search($order->status, $statusFlow) >= array_search($status, $statusFlow);
            $history[] = [
                'status' => $status,
                'completed' => $isCompleted,
                'date' => $isCompleted ? $order->updated_at->format('Y-m-d H:i:s') : null
            ];
        }

        return $history;
    }
}
