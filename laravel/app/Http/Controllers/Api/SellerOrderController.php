<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

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
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->with('buyer')->findOrFail($orderId);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        $order->status = $newStatus;
        $order->save();

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

        return response()->json([
            'total' => $totalOrders,
            'pending' => $pendingOrders,
            'processing' => $processingOrders,
            'shipped' => $shippedOrders,
            'delivered' => $deliveredOrders
        ]);
    }
}
