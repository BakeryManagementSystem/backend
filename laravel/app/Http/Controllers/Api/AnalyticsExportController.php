<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AnalyticsExportController extends Controller
{
    /**
     * Export analytics report as PDF
     */
    public function exportAnalytics(Request $request)
    {
        try {
            $user = Auth::user();
            $timeRange = $request->get('timeRange', '30days');
            $reportType = $request->get('reportType', 'overview');

            // Calculate date range
            $dateRange = $this->getDateRange($timeRange);

            // Fetch analytics data
            $analyticsData = $this->getAnalyticsData($user->id, $dateRange);

            // Prepare report data
            $reportData = [
                'user' => $user,
                'timeRange' => $timeRange,
                'reportType' => $reportType,
                'dateRange' => $dateRange,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'data' => $analyticsData
            ];

            // Generate PDF based on report type
            $viewName = $reportType === 'detailed' ? 'reports.detailed-analytics' : 'reports.analytics-overview';
            $pdf = Pdf::loadView($viewName, $reportData);
            $pdf->setPaper('A4', 'portrait');

            $fileName = "analytics-report-{$timeRange}-" . now()->format('Y-m-d') . ".pdf";
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export analytics: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Preview analytics report in browser
     */
    public function previewAnalytics(Request $request)
    {
        try {
            $user = Auth::user();
            $timeRange = $request->get('timeRange', '30days');
            $reportType = $request->get('reportType', 'overview');

            $dateRange = $this->getDateRange($timeRange);
            $analyticsData = $this->getAnalyticsData($user->id, $dateRange);

            $reportData = [
                'user' => $user,
                'timeRange' => $timeRange,
                'reportType' => $reportType,
                'dateRange' => $dateRange,
                'generatedAt' => now()->format('Y-m-d H:i:s'),
                'data' => $analyticsData
            ];

            $viewName = $reportType === 'detailed' ? 'reports.detailed-analytics' : 'reports.analytics-overview';
            $pdf = Pdf::loadView($viewName, $reportData);
            $pdf->setPaper('A4', 'portrait');

            $fileName = "analytics-report-{$timeRange}-" . now()->format('Y-m-d') . ".pdf";
            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to preview analytics: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get date range based on time range string
     */
    private function getDateRange($timeRange)
    {
        $endDate = Carbon::now();

        switch ($timeRange) {
            case '7days':
                $startDate = $endDate->copy()->subDays(7);
                break;
            case '30days':
                $startDate = $endDate->copy()->subDays(30);
                break;
            case '90days':
                $startDate = $endDate->copy()->subDays(90);
                break;
            case '1year':
                $startDate = $endDate->copy()->subYear();
                break;
            default:
                $startDate = $endDate->copy()->subDays(30);
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'label' => $this->getTimeRangeLabel($timeRange)
        ];
    }

    /**
     * Get analytics data for the user and date range
     */
    private function getAnalyticsData($userId, $dateRange)
    {
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Get orders for the user's products within date range
        $orders = Order::whereHas('orderItems.product', function($query) use ($userId) {
            $query->where('owner_id', $userId);
        })->whereBetween('created_at', [$startDate, $endDate])
        ->with(['orderItems.product', 'buyer'])
        ->get();

        // Calculate metrics
        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Get products
        $products = Product::where('owner_id', $userId)->get();
        $totalProducts = $products->count();
        $activeProducts = $products->where('is_active', true)->count();
        $lowStockProducts = $products->where('stock_quantity', '<', 10)->count();

        // Sales by day
        $salesByDay = $orders->groupBy(function($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function($dayOrders) {
            return [
                'date' => $dayOrders->first()->created_at->format('Y-m-d'),
                'revenue' => $dayOrders->sum('total'),
                'orders' => $dayOrders->count()
            ];
        })->values();

        // Top selling products
        $topProducts = $orders->flatMap->orderItems
            ->groupBy('product_id')
            ->map(function($items) {
                $product = $items->first()->product;
                return [
                    'name' => $product->name ?? 'Unknown Product',
                    'quantity_sold' => $items->sum('quantity'),
                    'revenue' => $items->sum(function($item) {
                        return $item->quantity * $item->unit_price;
                    })
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        // Order status breakdown
        $ordersByStatus = $orders->groupBy('status')->map->count();

        return [
            'overview' => [
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
                'averageOrderValue' => $averageOrderValue,
                'totalProducts' => $totalProducts,
                'activeProducts' => $activeProducts,
                'lowStockProducts' => $lowStockProducts
            ],
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
            'ordersByStatus' => $ordersByStatus,
            'orders' => $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'total' => $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                    'buyer_name' => $order->buyer->name ?? 'Unknown',
                    'items_count' => $order->orderItems->count()
                ];
            })
        ];
    }

    /**
     * Get human readable time range label
     */
    private function getTimeRangeLabel($timeRange)
    {
        switch ($timeRange) {
            case '7days':
                return 'Last 7 Days';
            case '30days':
                return 'Last 30 Days';
            case '90days':
                return 'Last 90 Days';
            case '1year':
                return 'Last Year';
            default:
                return 'Last 30 Days';
        }
    }
}
