<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\ShopProfile;
use App\Models\IngredientBatch;
use App\Models\OrderIngredientCost;
use Illuminate\Support\Facades\DB;

class AIAssistantController extends Controller
{
    /**
     * Process AI chat request
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'context' => 'nullable|array'
        ]);

        $user = $request->user();
        $message = strtolower(trim($request->message));
        $context = $request->context ?? [];

        // Analyze the message and determine intent
        $intent = $this->analyzeIntent($message);

        // Generate response based on intent
        $response = $this->generateResponse($intent, $message, $user, $context);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $response['message'],
                'data' => $response['data'] ?? null,
                'suggestions' => $response['suggestions'] ?? [],
                'actions' => $response['actions'] ?? []
            ]
        ]);
    }

    /**
     * Analyze user intent from message
     */
    private function analyzeIntent($message)
    {
        $intents = [
            'sales_inquiry' => ['sales', 'revenue', 'earnings', 'income', 'made', 'profit'],
            'order_inquiry' => ['orders', 'purchases', 'bought', 'sold', 'order status', 'pending'],
            'product_inquiry' => ['products', 'items', 'inventory', 'stock', 'catalog'],
            'analytics_inquiry' => ['analytics', 'statistics', 'stats', 'performance', 'trends', 'overview', 'business'],
            'customer_inquiry' => ['customers', 'buyers', 'users', 'clients'],
            'ingredient_inquiry' => ['ingredient', 'ingredients', 'investment', 'costs', 'expenses', 'low stock', 'expired', 'expiring', 'expiry', 'batch', 'batches', 'supplier', 'flour', 'butter', 'sugar'],
            'shop_inquiry' => ['shop', 'store', 'profile', 'about'],
            'recommendation' => ['recommend', 'suggest', 'best', 'top', 'popular'],
            'comparison' => ['compare', 'difference', 'versus', 'vs'],
            'help' => ['help', 'how to', 'guide', 'tutorial', 'assist']
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * Generate response based on intent
     */
    private function generateResponse($intent, $message, $user, $context)
    {
        switch ($intent) {
            case 'sales_inquiry':
                return $this->handleSalesInquiry($message, $user);

            case 'order_inquiry':
                return $this->handleOrderInquiry($message, $user);

            case 'product_inquiry':
                return $this->handleProductInquiry($message, $user);

            case 'analytics_inquiry':
                return $this->handleAnalyticsInquiry($message, $user);

            case 'customer_inquiry':
                return $this->handleCustomerInquiry($message, $user);

            case 'ingredient_inquiry':
                return $this->handleIngredientInquiry($message, $user);

            case 'shop_inquiry':
                return $this->handleShopInquiry($message, $user);

            case 'recommendation':
                return $this->handleRecommendation($message, $user);

            case 'comparison':
                return $this->handleComparison($message, $user);

            case 'help':
                return $this->handleHelp($message, $user);

            default:
                return $this->handleGeneral($message, $user);
        }
    }

    /**
     * Handle sales inquiry
     */
    private function handleSalesInquiry($message, $user)
    {
        $isSeller = $user->user_type === 'seller' || $user->user_type === 'owner';

        if ($isSeller) {
            $totalRevenue = OrderItem::where('owner_id', $user->id)
                ->whereHas('order', function($q) {
                    $q->whereIn('status', ['delivered', 'shipped']);
                })->sum('line_total');

            $todayRevenue = OrderItem::where('owner_id', $user->id)
                ->whereHas('order', function($q) {
                    $q->whereIn('status', ['delivered', 'shipped'])
                      ->whereDate('created_at', today());
                })->sum('line_total');

            $monthlyRevenue = OrderItem::where('owner_id', $user->id)
                ->whereHas('order', function($q) {
                    $q->whereIn('status', ['delivered', 'shipped'])
                      ->whereMonth('created_at', now()->month);
                })->sum('line_total');

            $topProduct = Product::where('owner_id', $user->id)
                ->withSum(['orderItems as revenue' => function($q) {
                    $q->whereHas('order', function($order) {
                        $order->whereIn('status', ['delivered', 'shipped']);
                    });
                }], 'line_total')
                ->orderBy('revenue', 'desc')
                ->first();

            return [
                'message' => "Here's your sales overview:\n\n" .
                    "💰 Total Revenue: $" . number_format($totalRevenue, 2) . "\n" .
                    "📅 Today's Revenue: $" . number_format($todayRevenue, 2) . "\n" .
                    "📊 This Month: $" . number_format($monthlyRevenue, 2) . "\n" .
                    ($topProduct ? "🏆 Top Product: {$topProduct->name} ($" . number_format($topProduct->revenue, 2) . ")" : ""),
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'today_revenue' => $todayRevenue,
                    'monthly_revenue' => $monthlyRevenue,
                    'top_product' => $topProduct
                ],
                'suggestions' => [
                    'Show me my best-selling products',
                    'What were my sales last month?',
                    'Compare my sales trends'
                ]
            ];
        } else {
            $totalSpent = Order::where('buyer_id', $user->id)
                ->whereIn('status', ['delivered', 'shipped'])
                ->sum('total_amount');

            $orderCount = Order::where('buyer_id', $user->id)->count();

            return [
                'message' => "Here's your purchase history:\n\n" .
                    "💳 Total Spent: $" . number_format($totalSpent, 2) . "\n" .
                    "📦 Total Orders: {$orderCount}",
                'data' => [
                    'total_spent' => $totalSpent,
                    'order_count' => $orderCount
                ]
            ];
        }
    }

    /**
     * Handle order inquiry
     */
    private function handleOrderInquiry($message, $user)
    {
        $isSeller = $user->user_type === 'seller' || $user->user_type === 'owner';

        if ($isSeller) {
            $pendingOrders = Order::whereHas('orderItems', function($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->where('status', 'pending')->count();

            $processingOrders = Order::whereHas('orderItems', function($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->where('status', 'processing')->count();

            $totalOrders = Order::whereHas('orderItems', function($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->count();

            $recentOrder = Order::whereHas('orderItems', function($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->latest()->first();

            return [
                'message' => "📦 Order Status:\n\n" .
                    "⏳ Pending: {$pendingOrders} orders\n" .
                    "⚙️ Processing: {$processingOrders} orders\n" .
                    "📊 Total Orders: {$totalOrders}\n" .
                    ($recentOrder ? "🆕 Latest Order: #{$recentOrder->id} - {$recentOrder->status}" : ""),
                'data' => [
                    'pending' => $pendingOrders,
                    'processing' => $processingOrders,
                    'total' => $totalOrders,
                    'recent_order' => $recentOrder
                ],
                'suggestions' => [
                    'Show pending orders',
                    'Which orders need shipping?',
                    'Order details for today'
                ],
                'actions' => $pendingOrders > 0 ? [
                    ['label' => 'View Pending Orders', 'action' => 'navigate', 'path' => '/seller/orders?status=pending']
                ] : []
            ];
        } else {
            $orders = Order::where('buyer_id', $user->id)->get();
            $pending = $orders->where('status', 'pending')->count();
            $shipped = $orders->where('status', 'shipped')->count();
            $delivered = $orders->where('status', 'delivered')->count();

            return [
                'message' => "📦 Your Orders:\n\n" .
                    "⏳ Pending: {$pending}\n" .
                    "🚚 Shipped: {$shipped}\n" .
                    "✅ Delivered: {$delivered}\n" .
                    "📊 Total: {$orders->count()}",
                'data' => [
                    'pending' => $pending,
                    'shipped' => $shipped,
                    'delivered' => $delivered,
                    'total' => $orders->count()
                ]
            ];
        }
    }

    /**
     * Handle product inquiry
     */
    private function handleProductInquiry($message, $user)
    {
        $isSeller = $user->user_type === 'seller' || $user->user_type === 'owner';

        if ($isSeller) {
            $totalProducts = Product::where('owner_id', $user->id)->count();
            $lowStock = Product::where('owner_id', $user->id)
                ->where('stock_quantity', '<', 10)
                ->count();

            $topSellingProducts = Product::where('owner_id', $user->id)
                ->withCount(['orderItems as sales'])
                ->orderBy('sales', 'desc')
                ->take(3)
                ->get();

            $productList = $topSellingProducts->map(function($p) {
                return "• {$p->name} ({$p->sales} sales)";
            })->implode("\n");

            return [
                'message' => "📦 Product Inventory:\n\n" .
                    "📊 Total Products: {$totalProducts}\n" .
                    "⚠️ Low Stock Items: {$lowStock}\n\n" .
                    "🏆 Top Sellers:\n{$productList}",
                'data' => [
                    'total' => $totalProducts,
                    'low_stock' => $lowStock,
                    'top_selling' => $topSellingProducts
                ],
                'suggestions' => [
                    'Show me low stock products',
                    'Which products sell best?',
                    'Add new product'
                ],
                'actions' => $lowStock > 0 ? [
                    ['label' => 'View Low Stock Products', 'action' => 'navigate', 'path' => '/seller/products?filter=low-stock']
                ] : []
            ];
        } else {
            $availableProducts = Product::where('status', 'active')->count();
            $categories = Product::distinct('category')->pluck('category');

            return [
                'message' => "🛍️ Available Products: {$availableProducts}\n" .
                    "📂 Categories: " . $categories->count() . "\n\n" .
                    "Browse by: " . $categories->take(5)->implode(', '),
                'data' => [
                    'total_products' => $availableProducts,
                    'categories' => $categories
                ]
            ];
        }
    }

    /**
     * Handle analytics inquiry
     */
    private function handleAnalyticsInquiry($message, $user)
    {
        if ($user->user_type !== 'seller' && $user->user_type !== 'owner') {
            return [
                'message' => "Analytics are available for sellers. As a buyer, you can view your order history and spending patterns.",
                'suggestions' => ['Show my orders', 'How much have I spent?']
            ];
        }

        $totalRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped']);
            })->sum('line_total');

        $totalOrders = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->count();

        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $bestDay = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped']);
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(DB::raw('DATE(orders.created_at) as date'), DB::raw('SUM(order_items.line_total) as revenue'))
            ->groupBy('date')
            ->orderBy('revenue', 'desc')
            ->first();

        return [
            'message' => "📊 Performance Analytics:\n\n" .
                "💰 Total Revenue: $" . number_format($totalRevenue, 2) . "\n" .
                "📦 Total Orders: {$totalOrders}\n" .
                "💵 Avg Order Value: $" . number_format($avgOrderValue, 2) . "\n" .
                ($bestDay ? "🏆 Best Day: " . date('M d, Y', strtotime($bestDay->date)) . " ($" . number_format($bestDay->revenue, 2) . ")" : ""),
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
                'best_day' => $bestDay
            ],
            'suggestions' => [
                'Show me sales trends',
                'What are my peak selling times?',
                'Compare this month vs last month'
            ],
            'actions' => [
                ['label' => 'View Full Analytics', 'action' => 'navigate', 'path' => '/seller/analytics']
            ]
        ];
    }

    /**
     * Handle customer inquiry
     */
    private function handleCustomerInquiry($message, $user)
    {
        if ($user->user_type !== 'seller' && $user->user_type !== 'owner') {
            return [
                'message' => "Customer analytics are available for sellers only.",
                'suggestions' => ['Show my orders', 'Browse products']
            ];
        }

        $uniqueCustomers = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->distinct('buyer_id')->count('buyer_id');

        $repeatCustomers = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
        ->select('buyer_id', DB::raw('COUNT(*) as order_count'))
        ->groupBy('buyer_id')
        ->having('order_count', '>', 1)
        ->count();

        $topCustomer = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
        ->with('buyer')
        ->select('buyer_id', DB::raw('COUNT(*) as order_count'))
        ->groupBy('buyer_id')
        ->orderBy('order_count', 'desc')
        ->first();

        return [
            'message' => "👥 Customer Insights:\n\n" .
                "👤 Total Customers: {$uniqueCustomers}\n" .
                "🔄 Repeat Customers: {$repeatCustomers}\n" .
                "💫 Retention Rate: " . ($uniqueCustomers > 0 ? round(($repeatCustomers / $uniqueCustomers) * 100, 1) : 0) . "%\n" .
                ($topCustomer && $topCustomer->buyer ? "🏆 Top Customer: {$topCustomer->buyer->name} ({$topCustomer->order_count} orders)" : ""),
            'data' => [
                'unique_customers' => $uniqueCustomers,
                'repeat_customers' => $repeatCustomers,
                'top_customer' => $topCustomer
            ],
            'suggestions' => [
                'Who are my best customers?',
                'Show customer purchase patterns',
                'Customer retention strategies'
            ]
        ];
    }

    /**
     * Handle ingredient inquiry
     */
    private function handleIngredientInquiry($message, $user)
    {
        if ($user->user_type !== 'seller' && $user->user_type !== 'owner') {
            return [
                'message' => "Ingredient tracking is available for sellers only.",
                'suggestions' => ['Browse products', 'Show my orders']
            ];
        }

        // Import Ingredient model at the top of the class if not already imported
        $ingredients = \App\Models\Ingredient::where('owner_id', $user->id)->get();

        // Check for specific ingredient queries

        // LOW STOCK INGREDIENTS
        if (strpos($message, 'low stock') !== false || strpos($message, 'running low') !== false) {
            $lowStockIngredients = \App\Models\Ingredient::where('owner_id', $user->id)
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->get();

            if ($lowStockIngredients->count() > 0) {
                $ingredientList = $lowStockIngredients->map(function($ing) {
                    return "• {$ing->name}: {$ing->current_stock} {$ing->unit} (Min: {$ing->minimum_stock} {$ing->unit})\n  Supplier: {$ing->supplier}";
                })->implode("\n\n");

                return [
                    'message' => "⚠️ Low Stock Alert! ({$lowStockIngredients->count()} items)\n\n" .
                        "The following ingredients need to be reordered:\n\n{$ingredientList}",
                    'data' => ['low_stock_ingredients' => $lowStockIngredients],
                    'suggestions' => [
                        'Show ingredient suppliers',
                        'View ingredient batches',
                        'Add new ingredient batch'
                    ],
                    'actions' => [
                        ['label' => 'Manage Ingredients', 'action' => 'navigate', 'path' => '/seller/ingredients']
                    ]
                ];
            } else {
                return [
                    'message' => "✅ Great news! All ingredients are currently above minimum stock levels. Your inventory is well-stocked.",
                    'suggestions' => [
                        'View all ingredients',
                        'Check ingredient statistics',
                        'View ingredient batches'
                    ]
                ];
            }
        }

        // EXPIRED/EXPIRING INGREDIENTS
        if (strpos($message, 'expired') !== false || strpos($message, 'expiring') !== false || strpos($message, 'expiry') !== false) {
            $expiredBatches = IngredientBatch::where('owner_id', $user->id)
                ->where('expiry_date', '<', now())
                ->get();

            $expiringBatches = IngredientBatch::where('owner_id', $user->id)
                ->whereBetween('expiry_date', [now(), now()->addDays(30)])
                ->get();

            $message = "";

            if ($expiredBatches->count() > 0) {
                $expiredList = $expiredBatches->map(function($batch) {
                    return "• {$batch->ingredient_name} (Batch: {$batch->batch_number})\n  Expired: {$batch->expiry_date}";
                })->implode("\n\n");

                $message .= "❌ Expired Items ({$expiredBatches->count()}):\n\n{$expiredList}\n\n";
            }

            if ($expiringBatches->count() > 0) {
                $expiringList = $expiringBatches->map(function($batch) {
                    return "• {$batch->ingredient_name} (Batch: {$batch->batch_number})\n  Expires: {$batch->expiry_date}";
                })->implode("\n\n");

                $message .= "⚠️ Expiring Soon ({$expiringBatches->count()}):\n\n{$expiringList}";
            }

            if ($expiredBatches->count() === 0 && $expiringBatches->count() === 0) {
                $message = "✅ Good news! No expired ingredients and no items expiring in the next 30 days.";
            }

            return [
                'message' => $message,
                'data' => [
                    'expired_batches' => $expiredBatches,
                    'expiring_batches' => $expiringBatches
                ],
                'suggestions' => [
                    'View all ingredient batches',
                    'Check low stock items',
                    'Add new ingredient batch'
                ]
            ];
        }

        // INGREDIENT STATISTICS
        if (strpos($message, 'statistics') !== false || strpos($message, 'stats') !== false || strpos($message, 'overview') !== false) {
            $totalIngredients = $ingredients->count();
            $totalValue = $ingredients->sum(function($ing) {
                return $ing->current_stock * $ing->cost_per_unit;
            });

            $lowStockCount = $ingredients->filter(function($ing) {
                return $ing->current_stock <= $ing->minimum_stock;
            })->count();

            $expiredCount = IngredientBatch::where('owner_id', $user->id)
                ->where('expiry_date', '<', now())
                ->count();

            $monthlyBatches = IngredientBatch::where('owner_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->get();

            $monthlyUsage = $monthlyBatches->sum('total_cost');

            // Get top ingredients by value
            $topIngredients = $ingredients->sortByDesc(function($ing) {
                return $ing->current_stock * $ing->cost_per_unit;
            })->take(5);

            $topIngredientsList = $topIngredients->map(function($ing, $index) {
                $value = $ing->current_stock * $ing->cost_per_unit;
                return ($index + 1) . ". {$ing->name}: \${$value} ({$ing->current_stock} {$ing->unit})";
            })->implode("\n");

            return [
                'message' => "📊 Ingredient Statistics Overview:\n\n" .
                    "• Total Ingredients: {$totalIngredients}\n" .
                    "• Total Inventory Value: $" . number_format($totalValue, 2) . "\n" .
                    "• Low Stock Items: {$lowStockCount}\n" .
                    "• Expired Batches: {$expiredCount}\n" .
                    "• Monthly Usage: $" . number_format($monthlyUsage, 2) . "\n\n" .
                    "**Top Ingredients by Value:**\n{$topIngredientsList}",
                'data' => [
                    'total_ingredients' => $totalIngredients,
                    'total_value' => $totalValue,
                    'low_stock_count' => $lowStockCount,
                    'expired_count' => $expiredCount,
                    'monthly_usage' => $monthlyUsage,
                    'top_ingredients' => $topIngredients
                ],
                'suggestions' => [
                    'Show low stock items',
                    'Check expired items',
                    'View ingredient batches'
                ]
            ];
        }

        // DEFAULT: Show general investment & profit analysis
        $manualInvestment = IngredientBatch::where('owner_id', $user->id)->sum('total_cost');
        $orderBasedCosts = OrderIngredientCost::where('owner_id', $user->id)->sum('total_ingredient_cost');
        $totalInvestment = $manualInvestment + $orderBasedCosts;

        $monthlyInvestment = IngredientBatch::where('owner_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->sum('total_cost') +
            OrderIngredientCost::where('owner_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->sum('total_ingredient_cost');

        $totalRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped']);
            })->sum('line_total');

        $profit = $totalRevenue - $totalInvestment;
        $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;

        return [
            'message' => "💰 Investment & Profit Analysis:\n\n" .
                "📊 Total Investment: $" . number_format($totalInvestment, 2) . "\n" .
                "📅 This Month: $" . number_format($monthlyInvestment, 2) . "\n" .
                "💵 Total Revenue: $" . number_format($totalRevenue, 2) . "\n" .
                "🎯 Net Profit: $" . number_format($profit, 2) . "\n" .
                "📈 Profit Margin: " . number_format($profitMargin, 1) . "%",
            'data' => [
                'total_investment' => $totalInvestment,
                'monthly_investment' => $monthlyInvestment,
                'total_revenue' => $totalRevenue,
                'profit' => $profit,
                'profit_margin' => $profitMargin
            ],
            'suggestions' => [
                'Show ingredient statistics',
                'Check low stock items',
                'View expired ingredients'
            ],
            'actions' => [
                ['label' => 'Manage Ingredients', 'action' => 'navigate', 'path' => '/seller/ingredients']
            ]
        ];
    }

    /**
     * Handle shop inquiry
     */
    private function handleShopInquiry($message, $user)
    {
        if ($user->user_type !== 'seller' && $user->user_type !== 'owner') {
            return [
                'message' => "You can browse shops and products from our marketplace!",
                'suggestions' => ['Browse products', 'Show popular items', 'Search for shops']
            ];
        }

        $shop = ShopProfile::where('owner_id', $user->id)->first();
        $productCount = Product::where('owner_id', $user->id)->count();
        $orderCount = Order::whereHas('orderItems', function($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->count();

        return [
            'message' => "🏪 Shop Profile:\n\n" .
                "📝 Name: " . ($shop ? $shop->shop_name : $user->name . "'s Shop") . "\n" .
                "📦 Products: {$productCount}\n" .
                "🛒 Total Orders: {$orderCount}\n" .
                "⭐ Rating: 4.5/5.0",
            'data' => [
                'shop' => $shop,
                'product_count' => $productCount,
                'order_count' => $orderCount
            ],
            'suggestions' => [
                'Update shop profile',
                'Customize shop theme',
                'View shop analytics'
            ],
            'actions' => [
                ['label' => 'Manage Shop', 'action' => 'navigate', 'path' => '/seller/shop']
            ]
        ];
    }

    /**
     * Handle recommendation requests
     */
    private function handleRecommendation($message, $user)
    {
        if ($user->user_type === 'seller' || $user->user_type === 'owner') {
            $lowStockProducts = Product::where('owner_id', $user->id)
                ->where('stock_quantity', '<', 10)
                ->take(3)
                ->get();

            $recommendations = [];

            if ($lowStockProducts->count() > 0) {
                $recommendations[] = "🔔 Restock these low inventory items: " .
                    $lowStockProducts->pluck('name')->implode(', ');
            }

            $pendingOrders = Order::whereHas('orderItems', function($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->where('status', 'pending')->count();

            if ($pendingOrders > 0) {
                $recommendations[] = "📦 You have {$pendingOrders} pending orders to process";
            }

            $topProducts = Product::where('owner_id', $user->id)
                ->withCount('orderItems')
                ->orderBy('order_items_count', 'desc')
                ->take(3)
                ->get();

            if ($topProducts->count() > 0) {
                $recommendations[] = "🏆 Focus on promoting: " . $topProducts->pluck('name')->implode(', ');
            }

            return [
                'message' => "💡 Smart Recommendations:\n\n" . implode("\n\n", $recommendations),
                'data' => [
                    'low_stock' => $lowStockProducts,
                    'pending_orders' => $pendingOrders,
                    'top_products' => $topProducts
                ],
                'suggestions' => [
                    'Show actionable insights',
                    'What should I focus on today?',
                    'Growth strategies'
                ]
            ];
        } else {
            $popularProducts = Product::withCount('orderItems')
                ->orderBy('order_items_count', 'desc')
                ->take(5)
                ->get();

            $categories = Product::distinct('category')->pluck('category')->take(3);

            return [
                'message' => "🛍️ Recommended for You:\n\n" .
                    "🔥 Popular Products:\n" .
                    $popularProducts->map(function($p) {
                        return "• {$p->name} - $" . number_format($p->price, 2);
                    })->implode("\n") . "\n\n" .
                    "📂 Browse: " . $categories->implode(', '),
                'data' => [
                    'popular_products' => $popularProducts,
                    'categories' => $categories
                ]
            ];
        }
    }

    /**
     * Handle comparison requests
     */
    private function handleComparison($message, $user)
    {
        if ($user->user_type !== 'seller' && $user->user_type !== 'owner') {
            return [
                'message' => "Comparison features are available for sellers to compare their performance metrics.",
                'suggestions' => ['Show my orders', 'Browse products']
            ];
        }

        $thisMonthRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped'])
                  ->whereMonth('created_at', now()->month);
            })->sum('line_total');

        $lastMonthRevenue = OrderItem::where('owner_id', $user->id)
            ->whereHas('order', function($q) {
                $q->whereIn('status', ['delivered', 'shipped'])
                  ->whereMonth('created_at', now()->subMonth()->month);
            })->sum('line_total');

        $change = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;
        $trend = $change > 0 ? '📈' : ($change < 0 ? '📉' : '➡️');

        return [
            'message' => "📊 Performance Comparison:\n\n" .
                "This Month: $" . number_format($thisMonthRevenue, 2) . "\n" .
                "Last Month: $" . number_format($lastMonthRevenue, 2) . "\n" .
                "{$trend} Change: " . ($change > 0 ? '+' : '') . number_format($change, 1) . "%",
            'data' => [
                'this_month' => $thisMonthRevenue,
                'last_month' => $lastMonthRevenue,
                'change_percent' => $change
            ],
            'suggestions' => [
                'Compare by category',
                'Year over year comparison',
                'Best performing products'
            ]
        ];
    }

    /**
     * Handle help requests
     */
    private function handleHelp($message, $user)
    {
        $isSeller = $user->user_type === 'seller' || $user->user_type === 'owner';

        $helpTopics = $isSeller ? [
            "📦 Managing Products - Add, edit, and track your inventory",
            "🛒 Processing Orders - Handle customer orders efficiently",
            "💰 Viewing Analytics - Understand your sales performance",
            "🏪 Shop Settings - Customize your shop profile",
            "💵 Ingredient Costs - Track your investments and profit"
        ] : [
            "🛍️ Browsing Products - Find what you're looking for",
            "🛒 Placing Orders - Complete your purchase",
            "📦 Tracking Orders - Monitor your deliveries",
            "❤️ Wishlist - Save items for later",
            "⭐ Reviews - Share your experience"
        ];

        return [
            'message' => "👋 How can I help you?\n\n" . implode("\n", $helpTopics) .
                "\n\nJust ask me anything like:\n" .
                ($isSeller ?
                    "• 'Show my sales this month'\n• 'Which products need restocking?'\n• 'How many pending orders?'" :
                    "• 'Show popular products'\n• 'Where's my order?'\n• 'Find electronics'"),
            'suggestions' => $isSeller ? [
                'Show my dashboard',
                'What needs attention?',
                'Sales overview'
            ] : [
                'Browse products',
                'Show my orders',
                'Popular items'
            ]
        ];
    }

    /**
     * Handle general queries
     */
    private function handleGeneral($message, $user)
    {
        return [
            'message' => "I understand you're asking about: \"{$message}\"\n\n" .
                "I can help you with:\n" .
                "• Sales and revenue information\n" .
                "• Order status and history\n" .
                "• Product inventory and analytics\n" .
                "• Customer insights\n" .
                "• Shop management\n\n" .
                "Try asking something specific like 'Show my sales' or 'List pending orders'",
            'suggestions' => [
                'Show my overview',
                'What can you help me with?',
                'Quick stats'
            ]
        ];
    }
}

