<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $items = CartItem::with('product')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        $total = 0.0;
        $mapped = $items->map(function (CartItem $ci) use (&$total) {
            $p = $ci->product;
            $line = $ci->quantity * (float) $ci->unit_price;
            $total += $line;
            return [
                'product_id' => $ci->product_id,
                'name'       => $p?->name,
                'category'   => $p?->category,
                'price'      => number_format((float)$ci->unit_price, 2, '.', ''),
                'quantity'   => $ci->quantity,
                'line_total' => number_format($line, 2, '.', ''),
                'image_url'  => $p?->image_url,
            ];
        });

        return response()->json([
            'items' => $mapped,
            'total' => number_format($total, 2, '.', ''),
            'count' => $items->sum('quantity'),
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'product_id' => ['required','integer','exists:products,id'],
            'quantity'   => ['nullable','integer','min:1'],
        ]);

        $qty = max(1, (int) ($data['quantity'] ?? 1));
        $product = Product::findOrFail($data['product_id']);

        $item = CartItem::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->quantity += $qty;
            $item->save();
        } else {
            $item = CartItem::create([
                'user_id'    => $userId,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_price' => $product->price, // snapshot
            ]);
        }

        return response()->json(['message' => 'Added to cart', 'item' => $item->fresh()], 201);
    }

    public function update(Request $request, Product $product)
    {
        $userId = $request->user()->id;
        $data = $request->validate(['quantity' => ['required','integer']]);

        $item = CartItem::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if (!$item) return response()->json(['message' => 'Item not in cart'], 404);

        if ($data['quantity'] <= 0) {
            $item->delete();
            return response()->json(['message' => 'Item removed']);
        }

        $item->quantity = $data['quantity'];
        $item->save();

        return response()->json(['message' => 'Quantity updated', 'item' => $item->fresh()]);
    }

    public function destroy(Request $request, Product $product)
    {
        $userId = $request->user()->id;

        $deleted = CartItem::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->delete();

        if (!$deleted) return response()->json(['message' => 'Item not in cart'], 404);

        return response()->json(['message' => 'Item removed']);
    }

    public function clear(Request $request)
    {
        $userId = $request->user()->id;
        CartItem::where('user_id', $userId)->delete();
        return response()->json(['message' => 'Cart cleared']);
    }
}
