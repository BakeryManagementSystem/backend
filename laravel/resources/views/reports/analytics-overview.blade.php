<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analytics Report - {{ $data['overview']['totalRevenue'] ? '$' . number_format($data['overview']['totalRevenue'], 2) : 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .report-title {
            font-size: 28px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 10px;
        }
        .report-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin: 30px 0;
        }
        .metric-row {
            display: table-row;
        }
        .metric-item {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 5px;
        }
        .metric-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 15px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #7c3aed;
            color: white;
            font-weight: bold;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .chart-placeholder {
            background-color: #f3f4f6;
            border: 2px dashed #d1d5db;
            padding: 40px;
            text-align: center;
            color: #6b7280;
            margin: 20px 0;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        .summary-row {
            display: table-row;
        }
        .summary-cell {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-shipped { background-color: #dbeafe; color: #1e40af; }
        .status-delivered { background-color: #dcfce7; color: #166534; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <div class="report-title">Business Analytics Report</div>
        <div class="report-subtitle">{{ $dateRange['label'] }} ({{ $dateRange['start']->format('M d, Y') }} - {{ $dateRange['end']->format('M d, Y') }})</div>
        <div class="report-subtitle">Generated for: {{ $user->name }}</div>
        <div class="report-subtitle">Report Date: {{ $generatedAt }}</div>
    </div>

    <!-- Key Metrics Overview -->
    <div class="section">
        <div class="section-title">Key Performance Metrics</div>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-item">
                    <div class="metric-value">${{ number_format($data['overview']['totalRevenue'], 2) }}</div>
                    <div class="metric-label">Total Revenue</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($data['overview']['totalOrders']) }}</div>
                    <div class="metric-label">Total Orders</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">${{ number_format($data['overview']['averageOrderValue'], 2) }}</div>
                    <div class="metric-label">Avg Order Value</div>
                </div>
            </div>
        </div>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($data['overview']['totalProducts']) }}</div>
                    <div class="metric-label">Total Products</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($data['overview']['activeProducts']) }}</div>
                    <div class="metric-label">Active Products</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($data['overview']['lowStockProducts']) }}</div>
                    <div class="metric-label">Low Stock Items</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="section">
        <div class="section-title">Top Selling Products</div>
        @if(count($data['topProducts']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th class="text-right">Quantity Sold</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['topProducts'] as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td class="text-right">{{ number_format($product['quantity_sold']) }}</td>
                        <td class="text-right">${{ number_format($product['revenue'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No product sales data available for this period.</p>
        @endif
    </div>

    <!-- Order Status Breakdown -->
    <div class="section">
        <div class="section-title">Order Status Summary</div>
        @if(count($data['ordersByStatus']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['ordersByStatus'] as $status => $count)
                    <tr>
                        <td>
                            <span class="status-badge status-{{ $status }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="text-right">{{ $count }}</td>
                        <td class="text-right">{{ $data['overview']['totalOrders'] > 0 ? number_format(($count / $data['overview']['totalOrders']) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No order status data available for this period.</p>
        @endif
    </div>

    <!-- Recent Orders -->
    <div class="section">
        <div class="section-title">Recent Orders (Last 10)</div>
        @if(count($data['orders']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Items</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['orders']->take(10) as $order)
                    <tr>
                        <td>#{{ $order['id'] }}</td>
                        <td>{{ $order['buyer_name'] }}</td>
                        <td>{{ $order['created_at'] }}</td>
                        <td>
                            <span class="status-badge status-{{ $order['status'] }}">
                                {{ ucfirst($order['status']) }}
                            </span>
                        </td>
                        <td class="text-right">${{ number_format($order['total'], 2) }}</td>
                        <td class="text-center">{{ $order['items_count'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No orders found for this period.</p>
        @endif
    </div>

    <!-- Sales Trend Chart Placeholder -->
    <div class="section">
        <div class="section-title">Sales Trend</div>
        <div class="chart-placeholder">
            <p><strong>Daily Sales Summary</strong></p>
            @if(count($data['salesByDay']) > 0)
                <table class="table" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Orders</th>
                            <th class="text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['salesByDay']->take(7) as $day)
                        <tr>
                            <td>{{ $day['date'] }}</td>
                            <td class="text-right">{{ $day['orders'] }}</td>
                            <td class="text-right">${{ number_format($day['revenue'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No daily sales data available for this period.</p>
            @endif
        </div>
    </div>

    <div class="footer">
        <p><strong>Bakery Management System - Analytics Report</strong></p>
        <p>This report was automatically generated on {{ $generatedAt }}</p>
        <p>Report covers {{ $dateRange['label'] }} from {{ $dateRange['start']->format('M d, Y') }} to {{ $dateRange['end']->format('M d, Y') }}</p>
    </div>
</body>
</html>
