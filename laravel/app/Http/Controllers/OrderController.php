<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $buyerId = $request->user()->id;


        $cart = CartItem::with('product')
            ->where('user_id', $buyerId)->get();


        if ($cart->isEmpty() && $request->has('items')) {
            // Validate the provided cart items
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0'
            ]);


            $cart = collect($request->items)->map(function ($item) use ($buyerId) {
                $product = \App\Models\Product::find($item['id']);

                return (object) [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ];
            });
        }

        if ($cart->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        return DB::transaction(function () use ($buyerId, $cart, $request) {
            $total = $cart->sum(fn($ci) => $ci->quantity * (float)$ci->unit_price);

            // Create order without payment processing - use 'pending' which is more likely to exist
            $order = Order::create([
                'buyer_id'     => $buyerId,
                'status'       => 'pending', // Use the most basic status that should exist
                'total_amount' => $total,
            ]);

            $sellerNotifications = [];

            foreach ($cart as $ci) {
                $p = $ci->product;
                if (!$p) continue;

                $line = $ci->quantity * (float)$ci->unit_price;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $p->id,
                    'owner_id'   => (int)$p->owner_id,
                    'quantity'   => $ci->quantity,
                    'unit_price' => $ci->unit_price,
                    'line_total' => $line,
                ]);

                Purchase::create([
                    'owner_id'   => (int)$p->owner_id,
                    'buyer_id'   => $buyerId,
                    'order_id'   => $order->id,
                    'product_id' => $p->id,
                    'quantity'   => $ci->quantity,
                    'unit_price' => $ci->unit_price,
                    'line_total' => $line,
                    'sold_at'    => now(),
                ]);

                // Collect sellers for notifications
                if (!in_array($p->owner_id, $sellerNotifications)) {
                    $sellerNotifications[] = $p->owner_id;
                }

                // Update product stock
                if ($p->stock_quantity > 0) {
                    $p->decrement('stock_quantity', $ci->quantity);
                }
            }

            // Clear cart after successful order
            CartItem::where('user_id', $buyerId)->delete();

            // Send real notifications to sellers
            $buyer = $request->user();
            foreach ($sellerNotifications as $sellerId) {
                Notification::createOrderNotification(
                    $sellerId,
                    $order->id,
                    $buyer->name ?? 'Unknown Customer'
                );
            }

            return response()->json([
                'message' => 'Order placed successfully!',
                'order' => $order->load('orderItems.product'),
                'notification_sent_to_sellers' => $sellerNotifications
            ], 201);
        });
    }

    public function index(Request $request)
    {
        $buyerId = $request->user()->id;

        $orders = Order::with('orderItems.product')
            ->where('buyer_id', $buyerId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function show(Request $request, $id)
    {
        $buyerId = $request->user()->id;

        $order = Order::with('orderItems.product')
            ->where('buyer_id', $buyerId)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json(['order' => $order]);
    }

    public function ownerPurchases(Request $request)
    {
        $ownerId = $request->user()->id;

        $rows = Purchase::with('product', 'buyer')
            ->where('owner_id', $ownerId)
            ->orderBy('sold_at','desc')
            ->get();

        $total = $rows->sum(fn($r) => (float)$r->line_total);

        return response()->json([
            'purchases' => $rows,
            'total'     => number_format($total, 2, '.', ''),
            'count'     => $rows->count(),
        ]);
    }

    public function buyNow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $buyerId = $request->user()->id;
        $productId = $request->product_id;
        $quantity = $request->quantity;

        return DB::transaction(function () use ($buyerId, $productId, $quantity) {
            $product = \App\Models\Product::find($productId);

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            if ($product->stock_quantity < $quantity) {
                return response()->json(['message' => 'Insufficient stock'], 422);
            }

            $unitPrice = $product->discount_price ?: $product->price;
            $lineTotal = $quantity * $unitPrice;

            // Create order without payment processing
            $order = Order::create([
                'buyer_id' => $buyerId,
                'status' => 'pending', // Use the most basic status that should exist
                'total_amount' => $lineTotal,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'owner_id' => $product->owner_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            Purchase::create([
                'owner_id' => $product->owner_id,
                'buyer_id' => $buyerId,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'sold_at' => now(),
            ]);

            // Update product stock
            $product->decrement('stock_quantity', $quantity);

            // Log notification for seller
            \Log::info("New order notification for seller ID: {$product->owner_id}, Order ID: {$order->id}");

            return response()->json([
                'message' => 'Order placed successfully!',
                'order' => $order->load('orderItems.product')
            ], 201);
        });
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update(['status' => $request->status]);

        // Create notification for buyer about status change
        if (class_exists('App\Models\Notification')) {
            \App\Models\Notification::create([
                'user_id' => $order->buyer_id,
                'type' => 'order_status_updated',
                'title' => 'Order Status Updated',
                'message' => "Your order #{$order->id} status has been updated to " . ucfirst($request->status),
                'data' => [
                    'order_id' => $order->id,
                    'new_status' => $request->status
                ]
            ]);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }
}
