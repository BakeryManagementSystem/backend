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
            $products = Product::select('id', 'name', 'price', 'description', 'category', 'stock_quantity')
                ->where('status', 'active')
                ->limit(50)
                ->get();

            $context['products'] = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'description' => $product->description,
                    'category' => $product->category,
                    'in_stock' => $product->stock_quantity > 0
                ];
            });

            // Get categories - use distinct categories from products
            $categories = Product::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->get()
                ->pluck('category')
                ->map(function ($category) {
                    return ['name' => $category];
                });
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

                // Get user's orders based on their type
                if ($user->user_type === 'buyer') {
                    // For buyers: get orders they placed
                    $orders = Order::where('buyer_id', $user->id)
                        ->select('id', 'total_amount', 'status', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();
                } else {
                    // For sellers: get orders containing their products
                    $orders = Order::whereHas('orderItems', function($query) use ($user) {
                        $query->where('owner_id', $user->id);
                    })
                    ->select('id', 'total_amount', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                }

                $context['orders'] = $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'total' => $order->total_amount,
                        'status' => $order->status,
                        'date' => $order->created_at->format('Y-m-d H:i:s')
                    ];
                });

                // Add account balance with all orders for accurate totals
                if ($user->user_type === 'buyer') {
                    $allOrders = Order::where('buyer_id', $user->id)->get();
                    $context['balance'] = [
                        'total_orders' => $allOrders->count(),
                        'total_spent' => $allOrders->sum('total_amount'),
                        'last_order_date' => $allOrders->sortByDesc('created_at')->first() ? $allOrders->sortByDesc('created_at')->first()->created_at->format('Y-m-d') : null
                    ];
                } else {
                    // For sellers: calculate sales from their order items
                    $sellerOrderItems = \App\Models\OrderItem::where('owner_id', $user->id)->get();
                    $sellerOrders = Order::whereHas('orderItems', function($query) use ($user) {
                        $query->where('owner_id', $user->id);
                    })->get();

                    $context['balance'] = [
                        'total_orders' => $sellerOrders->count(),
                        'total_earned' => $sellerOrderItems->sum('line_total'),
                        'last_order_date' => $sellerOrders->sortByDesc('created_at')->first() ? $sellerOrders->sortByDesc('created_at')->first()->created_at->format('Y-m-d') : null
                    ];
                }
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
            $query = Product::select('id', 'name', 'price', 'description', 'category', 'stock_quantity', 'image_path')
                ->where('status', 'active');

            // Apply search if provided
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply category filter if provided
            if ($request->has('category')) {
                $query->where('category', $request->get('category'));
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
            // Get distinct categories from products table
            $categories = Product::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->get()
                ->map(function ($product, $index) {
                    return [
                        'id' => $index + 1,
                        'name' => $product->category,
                        'products_count' => Product::where('category', $product->category)->count()
                    ];
                });

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
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null
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

            if ($user->user_type === 'buyer') {
                // For buyers: get orders they placed
                $orders = Order::where('buyer_id', $user->id)
                    ->select('id', 'total_amount', 'status', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->with('orderItems')
                    ->limit(20)
                    ->get();
            } else {
                // For sellers: get orders containing their products
                $orders = Order::whereHas('orderItems', function($query) use ($user) {
                    $query->where('owner_id', $user->id);
                })
                ->select('id', 'total_amount', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->with(['orderItems' => function($query) use ($user) {
                    $query->where('owner_id', $user->id);
                }])
                ->limit(20)
                ->get();
            }

            $formattedOrders = $orders->map(function ($order) use ($user) {
                return [
                    'id' => $order->id,
                    'total' => $user->user_type === 'buyer' ? $order->total_amount : $order->orderItems->sum('line_total'),
                    'status' => $order->status,
                    'date' => $order->created_at->format('Y-m-d H:i:s'),
                    'items_count' => $order->orderItems ? $order->orderItems->count() : 0,
                    'items' => $order->orderItems ? $order->orderItems->map(function ($item) {
                        return [
                            'product_name' => $item->product_name ?? 'Unknown',
                            'quantity' => $item->quantity,
                            'price' => $item->unit_price
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

            if ($user->user_type === 'buyer') {
                // For buyers: calculate spending
                $orders = Order::where('buyer_id', $user->id)->get();
                $balance = [
                    'total_orders' => $orders->count(),
                    'total_spent' => $orders->sum('total_amount'),
                    'pending_orders' => $orders->where('status', 'pending')->count(),
                    'completed_orders' => $orders->where('status', 'completed')->count(),
                    'last_order_date' => $orders->sortByDesc('created_at')->first()?->created_at?->format('Y-m-d'),
                    'average_order_value' => $orders->count() > 0 ? round($orders->sum('total_amount') / $orders->count(), 2) : 0
                ];
            } else {
                // For sellers: calculate earnings from their products
                $sellerOrderItems = \App\Models\OrderItem::where('owner_id', $user->id)->get();
                $sellerOrders = Order::whereHas('orderItems', function($query) use ($user) {
                    $query->where('owner_id', $user->id);
                })->get();

                $balance = [
                    'total_orders' => $sellerOrders->count(),
                    'total_earned' => $sellerOrderItems->sum('line_total'),
                    'pending_orders' => $sellerOrders->where('status', 'pending')->count(),
                    'completed_orders' => $sellerOrders->where('status', 'completed')->count(),
                    'last_order_date' => $sellerOrders->sortByDesc('created_at')->first()?->created_at?->format('Y-m-d'),
                    'average_order_value' => $sellerOrders->count() > 0 ? round($sellerOrderItems->sum('line_total') / $sellerOrders->count(), 2) : 0
                ];
            }

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
