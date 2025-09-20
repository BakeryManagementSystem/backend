<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['orderItems.product'])
            ->where('buyer_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.street' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'shipping_address.zipCode' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Get cart items
            $cartItems = CartItem::with('product')
                ->where('user_id', Auth::id())
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['error' => 'Cart is empty'], 400);
            }

            // Calculate total
            $total = $cartItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

            // Create order
            $order = Order::create([
                'buyer_id' => Auth::id(),
                'total_amount' => $total,
                'status' => 'pending'
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'owner_id' => $cartItem->product->owner_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'line_total' => $cartItem->quantity * $cartItem->unit_price
                ]);
            }

            // Clear cart
            CartItem::where('user_id', Auth::id())->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('orderItems.product')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Order placement failed'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,shipped,delivered,cancelled'
        ]);

        $order = Order::findOrFail($id);

        // Check if user can update this order (seller or admin)
        $orderItems = OrderItem::where('order_id', $id)->get();
        $canUpdate = $orderItems->contains('owner_id', Auth::id()) ||
                    $order->buyer_id === Auth::id();

        if (!$canUpdate) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $order->update(['status' => $request->status]);

        return response()->json($order);
    }

    public function getSellerOrders()
    {
        $orders = OrderItem::with(['order.buyer', 'product'])
            ->where('owner_id', Auth::id())
            ->get()
            ->groupBy('order_id')
            ->map(function ($items) {
                $order = $items->first()->order;
                return [
                    'id' => $order->id,
                    'customer' => $order->buyer,
                    'items' => $items,
                    'total' => $items->sum('line_total'),
                    'status' => $order->status,
                    'orderDate' => $order->created_at,
                ];
            })
            ->values();

        return response()->json($orders);
    }
}
