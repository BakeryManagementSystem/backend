<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    /**
     * Generate and download invoice PDF for a specific order
     */
    public function generateInvoice($orderId)
    {
        try {
            $user = Auth::user();

            // Get the order with related data
            $order = Order::with(['orderItems.product', 'buyer'])
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Check if user owns this order (seller) or is the buyer
            $isOwner = $order->orderItems->first()?->product?->owner_id === $user->id;
            $isBuyer = $order->buyer_id === $user->id;

            if (!$isOwner && !$isBuyer) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Prepare invoice data
            $invoiceData = [
                'order' => $order,
                'seller' => $order->orderItems->first()?->product?->owner,
                'buyer' => $order->buyer,
                'items' => $order->orderItems,
                'subtotal' => $order->orderItems->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                }),
                'tax' => $order->total * 0.1, // Assuming 10% tax
                'total' => $order->total,
                'invoiceNumber' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'invoiceDate' => $order->created_at->format('Y-m-d'),
                'dueDate' => $order->created_at->addDays(30)->format('Y-m-d')
            ];

            // Generate PDF
            $pdf = Pdf::loadView('invoices.order-invoice', $invoiceData);

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Return PDF as download
            return $pdf->download("invoice-{$invoiceData['invoiceNumber']}.pdf");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Preview invoice in browser
     */
    public function previewInvoice($orderId)
    {
        try {
            $user = Auth::user();

            $order = Order::with(['orderItems.product', 'buyer'])
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            $isOwner = $order->orderItems->first()?->product?->owner_id === $user->id;
            $isBuyer = $order->buyer_id === $user->id;

            if (!$isOwner && !$isBuyer) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $invoiceData = [
                'order' => $order,
                'seller' => $order->orderItems->first()?->product?->owner,
                'buyer' => $order->buyer,
                'items' => $order->orderItems,
                'subtotal' => $order->orderItems->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                }),
                'tax' => $order->total * 0.1,
                'total' => $order->total,
                'invoiceNumber' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'invoiceDate' => $order->created_at->format('Y-m-d'),
                'dueDate' => $order->created_at->addDays(30)->format('Y-m-d')
            ];

            $pdf = Pdf::loadView('invoices.order-invoice', $invoiceData);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream("invoice-{$invoiceData['invoiceNumber']}.pdf");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to preview invoice: ' . $e->getMessage()], 500);
        }
    }
}
