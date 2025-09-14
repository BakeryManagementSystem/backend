<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
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

        if ($cart->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        return DB::transaction(function () use ($buyerId, $cart) {
            $total = $cart->sum(fn($ci) => $ci->quantity * (float)$ci->unit_price);

            $order = Order::create([
                'buyer_id'     => $buyerId,
                'buyer_address' => $address,
                'buyer_phone'   => $phone,
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




   public function buyNow(Request $request)
       {
           $validator = Validator::make($request->all(), [
               'product_id' => 'required|exists:products,id',
               'quantity' => 'required|integer|min:1',
               'buyer_name' => 'required|string|max:255',
               'buyer_email' => 'required|email|max:255',
               'buyer_phone' => 'required|string|max:20',
               'buyer_address' => 'required|string|max:500',
           ]);

           if ($validator->fails()) {
               return response()->json([
                   'message' => 'Validation failed',
                   'errors' => $validator->errors()
               ], 422);
           }

           $buyerId = $request->user()->id;
           $productId = $request->product_id;
           $quantity = $request->quantity;

           $product = Product::findOrFail($productId);

           return DB::transaction(function () use ($request, $buyerId, $product, $quantity) {
               $lineTotal = $quantity * (float)$product->price;

               // Create order
               $order = Order::create([
                   'buyer_id' => $buyerId,
                   'buyer_phone' => $request->buyer_phone,
                   'buyer_address' => $request->buyer_address,
                   'status' => 'pending',
                   'total_amount' => $lineTotal,
               ]);

               // Create order item
               OrderItem::create([
                   'order_id' => $order->id,
                   'product_id' => $product->id,
                   'owner_id' => $product->owner_id,
                   'quantity' => $quantity,
                   'unit_price' => $product->price,
                   'line_total' => $lineTotal,
               ]);

               // Create purchase record
               Purchase::create([
                   'owner_id' => $product->owner_id,
                   'buyer_id' => $buyerId,
                   'order_id' => $order->id,
                   'product_id' => $product->id,
                   'quantity' => $quantity,
                   'unit_price' => $product->price,
                   'line_total' => $lineTotal,
                   'sold_at' => now(),
               ]);

               // TODO: Send notification to product owner
               // You can implement this using Laravel's notification system

               return response()->json([
                   'message' => 'Order placed successfully',
                   'order' => $order->load('items.product')
               ], 201);
           });
       }

       public function getUserProfile(Request $request)
       {
           $user = $request->user();
           return response()->json([
               'name' => $user->name,
               'email' => $user->email,
               'user_type' => $user->user_type,
           ]);
       }


       public function getOwnerInbox(Request $request)
           {
               try {
                   $ownerId = $request->user()->id;

                   $notifications = DB::table('purchases as p')
                       ->join('products as pr', 'p.product_id', '=', 'pr.id')
                       ->join('users as u', 'p.buyer_id', '=', 'u.id')
                       ->join('orders as o', 'p.order_id', '=', 'o.id')
                       ->where('pr.owner_id', $ownerId)
                       ->select(
                           'p.id as purchase_id',
                           'p.quantity',
                           'p.line_total',
                           'pr.name as product_name',
                           'u.name as buyer_name',
                           'u.email as buyer_email',
                           'o.buyer_phone',
                           'o.buyer_address',
                           'o.created_at as order_date',
                           'o.status as order_status'
                       )
                       ->orderBy('o.created_at', 'desc')
                       ->get();

                   return response()->json([
                       'success' => true,
                       'notifications' => $notifications
                   ]);

               } catch (\Exception $e) {
                   return response()->json([
                       'success' => false,
                       'message' => 'Failed to fetch inbox data',
                       'error' => $e->getMessage()
                   ], 500);
               }
           }

             public function getOwnerOrders(Request $request)
             {
                 try {
                     $ownerId = $request->user()->id;

                     $orders = DB::table('order_items as oi')
                         ->join('products as p', 'oi.product_id', '=', 'p.id')
                         ->join('orders as o', 'oi.order_id', '=', 'o.id')
                         ->join('users as u', 'o.buyer_id', '=', 'u.id')
                         ->where('oi.owner_id', $ownerId)
                         ->select(
                             'oi.id as order_item_id',
                             'oi.order_id',
                             'oi.product_id',
                             'oi.quantity',
                             'oi.unit_price',
                             'oi.line_total',
                             'p.name as product_name',
                             'p.description as product_description',
                             'p.category as product_category',
                             DB::raw('CASE WHEN p.image_path IS NULL OR p.image_path = "" THEN NULL ELSE p.image_path END as product_image'),
                             'o.status as order_status',
                             'o.buyer_address',
                             'o.buyer_phone',
                             'o.created_at as order_date',
                             'u.name as buyer_name',
                             'u.email as buyer_email'
                         )
                         ->orderBy('o.created_at', 'desc')
                         ->get();

                     return response()->json([
                         'success' => true,
                         'orders' => $orders,
                     ]);
                 } catch (\Throwable $e) {
                     return response()->json([
                         'success' => false,
                         'message' => 'Failed to fetch orders data',
                         'error' => $e->getMessage(),
                     ], 500);
                 }
             }

             public function updateOrderStatus(Request $request, int $orderId)
             {
                 $request->validate([
                     'status' => 'required|in:accepted,rejected',
                 ]);

                 $ownerId = $request->user()->id;
                 $status  = $request->input('status');

                 try {
                     return DB::transaction(function () use ($orderId, $ownerId, $status) {
                         // Ensure this owner has items in this order
                         $ownerHasItems = OrderItem::where('order_id', $orderId)
                             ->where('owner_id', $ownerId)
                             ->exists();

                         if (!$ownerHasItems) {
                             return response()->json([
                                 'success' => false,
                                 'message' => 'Order not found for this owner',
                             ], 404);
                         }

                         // Ensure order exists
                         $order = Order::lockForUpdate()->find($orderId);
                         if (!$order) {
                             return response()->json([
                                 'success' => false,
                                 'message' => 'Order not found',
                             ], 404);
                         }

                         // Optional: guard against re-updating finalized orders
                         if (in_array($order->status, ['accepted', 'terminated'], true) && $status !== $order->status) {
                             // Allow idempotent update to same status, block conflicting transitions
                             return response()->json([
                                 'success' => false,
                                 'message' => 'Order already finalized',
                             ], 422);
                         }

                         // Map 'rejected' input to 'terminated' status
                         $newStatus = $status === 'rejected' ? 'terminated' : $status;

                         // Update order status
                         $order->update(['status' => $newStatus]);

                         // If rejected (terminated), remove only this owner's rows
                         if ($status === 'rejected') {
                             OrderItem::where('order_id', $orderId)
                                 ->where('owner_id', $ownerId)
                                 ->delete();

                             Purchase::where('order_id', $orderId)
                                 ->where('owner_id', $ownerId)
                                 ->delete();

                             // If no items remain for this order, optionally delete the order
                             $remainingItems = OrderItem::where('order_id', $orderId)->exists();
                             if (!$remainingItems) {
                                 // If you prefer to keep the order record, remove this block
                                 // and only keep status as 'terminated'.
                                 // $order->delete();
                             }
                         }

                         return response()->json([
                             'success' => true,
                             'message' => "Order {$newStatus} successfully",
                         ]);
                     });
                 } catch (\Throwable $e) {
                     return response()->json([
                         'success' => false,
                         'message' => 'Failed to update order status',
                         'error' => $e->getMessage(),
                     ], 500);
                 }
             }


             public function getBuyerNotifications(Request $request)
             {
                 try {
                     $buyerId = $request->user()->id;

                     $notifications = DB::table('purchases as p')
                         ->join('products as pr', 'p.product_id', '=', 'pr.id')
                         ->join('orders as o', 'p.order_id', '=', 'o.id')
                         ->where('p.buyer_id', $buyerId)
                         ->select(
                             'p.id as purchase_id',
                             'p.quantity',
                             'p.line_total',
                             'pr.name as product_name',
                             'pr.description as product_description',
                             'pr.category as product_category',
                             DB::raw('CASE WHEN pr.image_path IS NULL OR pr.image_path = "" THEN NULL ELSE pr.image_path END as product_image'),
                             'o.status as order_status',
                             'o.created_at as order_date'
                         )
                         ->orderBy('o.created_at', 'desc')
                         ->get();

                     return response()->json([
                         'success' => true,
                         'notifications' => $notifications,
                     ]);
                 } catch (\Throwable $e) {
                     return response()->json([
                         'success' => false,
                         'message' => 'Failed to fetch notifications data',
                         'error' => $e->getMessage(),
                     ], 500);
                 }
             }

}
