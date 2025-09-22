<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detailed Analytics Report - {{ $user->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            line-height: 1.5;
            font-size: 12px;
        }
        .header {
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 8px;
        }
        .report-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }
        .section {
            margin: 25px 0;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 12px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 3px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .metrics-grid {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        .metric-row {
            display: table-row;
        }
        .metric-item {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .metric-value {
            font-size: 18px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 3px;
        }
        .metric-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-shipped { background-color: #dbeafe; color: #1e40af; }
        .status-delivered { background-color: #dcfce7; color: #166534; }
        .status-cancelled { background-color: #fee2e2; color: #dc2626; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="report-title">Detailed Business Analytics Report</div>
        <div class="report-subtitle">{{ $dateRange['label'] }} ({{ $dateRange['start']->format('M d, Y') }} - {{ $dateRange['end']->format('M d, Y') }})</div>
        <div class="report-subtitle">Generated for: {{ $user->name }}</div>
        <div class="report-subtitle">Report Date: {{ $generatedAt }}</div>
    </div>

    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">Executive Summary</div>
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
                <div class="metric-item">
                    <div class="metric-value">{{ number_format($data['overview']['totalProducts']) }}</div>
                    <div class="metric-label">Products</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Sales Breakdown -->
    <div class="section">
        <div class="section-title">Daily Sales Performance</div>
        @if(count($data['salesByDay']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Orders</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Avg Order Value</th>
                        <th class="text-right">% of Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['salesByDay'] as $day)
                    <tr>
                        <td>{{ $day['date'] }}</td>
                        <td class="text-right">{{ $day['orders'] }}</td>
                        <td class="text-right">${{ number_format($day['revenue'], 2) }}</td>
                        <td class="text-right">${{ $day['orders'] > 0 ? number_format($day['revenue'] / $day['orders'], 2) : '0.00' }}</td>
                        <td class="text-right">{{ $data['overview']['totalRevenue'] > 0 ? number_format(($day['revenue'] / $data['overview']['totalRevenue']) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No daily sales data available for this period.</p>
        @endif
    </div>

    <!-- Product Performance Analysis -->
    <div class="section page-break">
        <div class="section-title">Product Performance Analysis</div>
        @if(count($data['topProducts']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product Name</th>
                        <th class="text-right">Units Sold</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Avg Price</th>
                        <th class="text-right">% of Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['topProducts'] as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $product['name'] }}</td>
                        <td class="text-right">{{ number_format($product['quantity_sold']) }}</td>
                        <td class="text-right">${{ number_format($product['revenue'], 2) }}</td>
                        <td class="text-right">${{ $product['quantity_sold'] > 0 ? number_format($product['revenue'] / $product['quantity_sold'], 2) : '0.00' }}</td>
                        <td class="text-right">{{ $data['overview']['totalRevenue'] > 0 ? number_format(($product['revenue'] / $data['overview']['totalRevenue']) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No product performance data available for this period.</p>
        @endif
    </div>

    <!-- Order Status Analysis -->
    <div class="section">
        <div class="section-title">Order Status Analysis</div>
        @if(count($data['ordersByStatus']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th class="text-right">Count</th>
                        <th class="text-right">Percentage</th>
                        <th>Description</th>
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
                        <td>
                            @switch($status)
                                @case('pending')
                                    Orders awaiting processing
                                    @break
                                @case('shipped')
                                    Orders in transit to customers
                                    @break
                                @case('delivered')
                                    Successfully completed orders
                                    @break
                                @case('cancelled')
                                    Cancelled or refunded orders
                                    @break
                                @default
                                    Other status
                            @endswitch
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No order status data available for this period.</p>
        @endif
    </div>

    <!-- Detailed Order Listing -->
    <div class="section page-break">
        <div class="section-title">Complete Order Listing</div>
        @if(count($data['orders']) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th class="text-right">Items</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['orders'] as $order)
                    <tr>
                        <td>#{{ $order['id'] }}</td>
                        <td>{{ $order['buyer_name'] }}</td>
                        <td>{{ $order['created_at'] }}</td>
                        <td>
                            <span class="status-badge status-{{ $order['status'] }}">
                                {{ ucfirst($order['status']) }}
                            </span>
                        </td>
                        <td class="text-center">{{ $order['items_count'] }}</td>
                        <td class="text-right">${{ number_format($order['total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Summary Statistics -->
            <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <h4 style="margin: 0 0 10px 0; color: #7c3aed;">Summary Statistics</h4>
                <div style="display: table; width: 100%;">
                    <div style="display: table-row;">
                        <div style="display: table-cell; width: 25%; padding: 5px;">
                            <strong>Total Orders:</strong> {{ count($data['orders']) }}
                        </div>
                        <div style="display: table-cell; width: 25%; padding: 5px;">
                            <strong>Total Revenue:</strong> ${{ number_format($data['overview']['totalRevenue'], 2) }}
                        </div>
                        <div style="display: table-cell; width: 25%; padding: 5px;">
                            <strong>Avg Order Value:</strong> ${{ number_format($data['overview']['averageOrderValue'], 2) }}
                        </div>
                        <div style="display: table-cell; width: 25%; padding: 5px;">
                            <strong>Report Period:</strong> {{ $dateRange['label'] }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p>No orders found for this period.</p>
        @endif
    </div>

    <div class="footer">
        <p><strong>Bakery Management System - Detailed Analytics Report</strong></p>
        <p>This comprehensive report was automatically generated on {{ $generatedAt }}</p>
        <p>Data covers {{ $dateRange['label'] }} from {{ $dateRange['start']->format('M d, Y') }} to {{ $dateRange['end']->format('M d, Y') }}</p>
        <p>All financial figures are in USD. Report generated for {{ $user->name }} ({{ $user->email }})</p>
    </div>
</body>
</html>
