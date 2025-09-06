<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $buyerId = $request->user()->id;

        $cart = CartItem::with('product')
            ->where('user_id', $buyerId)->get();

        if ($cart->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        return DB::transaction(function () use ($buyerId, $cart) {
            $total = $cart->sum(fn($ci) => $ci->quantity * (float)$ci->unit_price);

            $order = Order::create([
                'buyer_id'     => $buyerId,
                'status'       => 'pending',
                'total_amount' => $total,
            ]);

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
            }

            CartItem::where('user_id', $buyerId)->delete();

            return response()->json(['message' => 'Order placed', 'order' => $order->load('items')], 201);
        });
    }

    public function index(Request $request)
    {
        $buyerId = $request->user()->id;

        $orders = Order::with('items.product')
            ->where('buyer_id', $buyerId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function ownerPurchases(Request $request)
    {
        $ownerId = $request->user()->id;

        $rows = Purchase::with('product')
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
}
