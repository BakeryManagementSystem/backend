<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoiceNumber }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .clearfix {
            clear: both;
        }
        .billing-section {
            margin: 30px 0;
        }
        .billing-info {
            float: left;
            width: 48%;
        }
        .shipping-info {
            float: right;
            width: 48%;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: #7c3aed;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .items-table th {
            background-color: #7c3aed;
            color: white;
            font-weight: bold;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 16px;
            background-color: #7c3aed;
            color: white;
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
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
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
        <div class="company-info">
            <h1>{{ $shop->shop_name ?? ($seller->name ?? 'Bakery Management System') }}</h1>
            <p>
                @if($shop && $shop->name)
                    Seller: {{ $shop->name }}<br>
                @elseif($seller && $seller->name)
                    Seller: {{ $seller->name }}<br>
                @endif

                @if($shop && $shop->phone)
                    Phone: {{ $shop->phone }}<br>
                @elseif($seller && $seller->phone)
                    Phone: {{ $seller->phone }}<br>
                @endif

                @if($shop && $shop->address)
                    Address: {{ $shop->address }}<br>
                @endif

                @if($seller && $seller->email)
                    Email: {{ $seller->email }}<br>
                @endif

                Date: {{ now()->format('Y-m-d') }}
            </p>
        </div>
        <div class="invoice-info">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">Invoice #: {{ $invoiceNumber }}</div>
            <div>Invoice Date: {{ $invoiceDate }}</div>
            <div>Due Date: {{ $dueDate }}</div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-{{ $order->status }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="billing-section">
        <div class="billing-info">
            <div class="section-title">Bill To:</div>
            <strong>{{ $buyer->name ?? 'Customer' }}</strong><br>
            @if($buyer && $buyer->email)
                {{ $buyer->email }}<br>
            @endif
            @if($order->shipping_address)
                {{ $order->shipping_address['street'] ?? '' }}<br>
                {{ $order->shipping_address['city'] ?? '' }},
                {{ $order->shipping_address['state'] ?? '' }}
                {{ $order->shipping_address['zipCode'] ?? '' }}
            @endif
        </div>
        <div class="shipping-info">
            <div class="section-title">Order Details:</div>
            <strong>Order ID:</strong> #{{ $order->id }}<br>
            <strong>Order Date:</strong> {{ $order->created_at->format('Y-m-d H:i') }}<br>
            <strong>Payment Status:</strong> {{ $order->payment_status ?? 'Pending' }}<br>
            <strong>Shipping Method:</strong> {{ $order->shipping_method ?? 'Standard' }}
        </div>
    </div>

    <div class="clearfix"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th class="text-center">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product->name ?? 'Product' }}</strong>
                    @if($item->product && $item->product->description)
                        <br><small style="color: #666;">{{ Str::limit($item->product->description, 60) }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Tax (10%):</td>
                <td class="text-right">${{ number_format($tax, 2) }}</td>
            </tr>
            @if($order->shipping_cost ?? 0 > 0)
            <tr>
                <td>Shipping:</td>
                <td class="text-right">${{ number_format($order->shipping_cost, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">${{ number_format($total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>This invoice was generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        @if($order->notes)
            <p><strong>Notes:</strong> {{ $order->notes }}</p>
        @endif
    </div>
</body>
</html>
