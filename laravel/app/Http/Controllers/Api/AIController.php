<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;

class AIController extends Controller
{
    /**
     * Get context data for AI assistant
     * This endpoint provides data for the AI to understand the bakery business
     */
    public function getContextData(Request $request)
    {
        try {
            $context = [
                'products' => [],
                'categories' => [],
                'shop_info' => [
                    'name' => 'Artisan Bakery',
                    'description' => 'Fresh baked goods made daily with love and traditional recipes',
                    'specialties' => ['Artisan Breads', 'Custom Cakes', 'Fresh Pastries', 'Wedding Cakes']
                ]
            ];

            // Get products with basic information
            $products = Product::select('id', 'name', 'price', 'description', 'category_id', 'stock_quantity')
                ->where('is_active', true)
                ->with('category:id,name')
                ->limit(50)
                ->get();

            $context['products'] = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'description' => $product->description,
                    'category' => $product->category ? $product->category->name : null,
                    'in_stock' => $product->stock_quantity > 0
                ];
            });

            // Get categories
            $categories = Category::select('id', 'name', 'description')->get();
            $context['categories'] = $categories;

            // If user is authenticated, add user-specific data
            if (Auth::check()) {
                $user = Auth::user();
                $context['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'created_at' => $user->created_at
                ];

                // Get user's recent orders
                $orders = Order::where('user_id', $user->id)
                    ->select('id', 'total_amount', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                $context['orders'] = $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'total' => $order->total_amount,
                        'status' => $order->status,
                        'date' => $order->created_at->format('Y-m-d H:i:s')
                    ];
                });

                // Add account balance or credit info if available
                $context['balance'] = [
                    'total_orders' => $orders->count(),
                    'total_spent' => $orders->sum('total_amount'),
                    'last_order_date' => $orders->first() ? $orders->first()->created_at->format('Y-m-d') : null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $context
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch context data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product information for AI queries
     */
    public function getProducts(Request $request)
    {
        try {
            $query = Product::select('id', 'name', 'price', 'description', 'category_id', 'stock_quantity', 'image_url')
                ->where('is_active', true)
                ->with('category:id,name');

            // Apply search if provided
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply category filter if provided
            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            $products = $query->limit(20)->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for AI queries
     */
    public function getCategories()
    {
        try {
            $categories = Category::select('id', 'name', 'description')
                ->withCount('products')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile information (for authenticated users)
     */
    public function getUserProfile()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = Auth::user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'phone' => $user->phone ?? null,
                    'address' => $user->address ?? null,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user orders (for authenticated users)
     */
    public function getUserOrders()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = Auth::user();
            $orders = Order::where('user_id', $user->id)
                ->select('id', 'total_amount', 'status', 'created_at', 'delivery_address')
                ->orderBy('created_at', 'desc')
                ->with('orderItems.product:id,name,price')
                ->limit(20)
                ->get();

            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'total' => $order->total_amount,
                    'status' => $order->status,
                    'date' => $order->created_at->format('Y-m-d H:i:s'),
                    'delivery_address' => $order->delivery_address,
                    'items_count' => $order->orderItems ? $order->orderItems->count() : 0,
                    'items' => $order->orderItems ? $order->orderItems->map(function ($item) {
                        return [
                            'product_name' => $item->product ? $item->product->name : 'Unknown',
                            'quantity' => $item->quantity,
                            'price' => $item->price
                        ];
                    }) : []
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user account balance/summary (for authenticated users)
     */
    public function getUserBalance()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = Auth::user();
            $orders = Order::where('user_id', $user->id)->get();

            $balance = [
                'total_orders' => $orders->count(),
                'total_spent' => $orders->sum('total_amount'),
                'pending_orders' => $orders->where('status', 'pending')->count(),
                'completed_orders' => $orders->where('status', 'delivered')->count(),
                'last_order_date' => $orders->sortByDesc('created_at')->first()?->created_at?->format('Y-m-d'),
                'average_order_value' => $orders->count() > 0 ? round($orders->sum('total_amount') / $orders->count(), 2) : 0
            ];

            return response()->json([
                'success' => true,
                'data' => $balance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
