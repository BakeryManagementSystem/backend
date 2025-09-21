<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function isRead()
    {
        return !is_null($this->read_at);
    }

    // Helper methods for creating notifications
    public static function createOrderNotification($sellerId, $orderId, $buyerName)
    {
        return self::create([
            'user_id' => $sellerId,
            'type' => 'new_order',
            'title' => 'New Order Received',
            'message' => "You have received a new order from {$buyerName}",
            'data' => [
                'order_id' => $orderId,
                'buyer_name' => $buyerName
            ]
        ]);
    }

    public static function createOrderStatusNotification($buyerId, $orderId, $status, $sellerName)
    {
        $statusMessages = [
            'processing' => 'Your order is now being processed',
            'shipped' => 'Your order has been shipped',
            'delivered' => 'Your order has been delivered',
            'cancelled' => 'Your order has been cancelled'
        ];

        $message = $statusMessages[$status] ?? "Your order status has been updated to {$status}";

        return self::create([
            'user_id' => $buyerId,
            'type' => 'order_status_update',
            'title' => 'Order Status Updated',
            'message' => "{$message} by {$sellerName}",
            'data' => [
                'order_id' => $orderId,
                'status' => $status,
                'seller_name' => $sellerName
            ]
        ]);
    }

    public static function createPaymentNotification($sellerId, $orderId, $amount, $buyerName)
    {
        return self::create([
            'user_id' => $sellerId,
            'type' => 'payment_received',
            'title' => 'Payment Received',
            'message' => "Payment of ${$amount} received from {$buyerName}",
            'data' => [
                'order_id' => $orderId,
                'amount' => $amount,
                'buyer_name' => $buyerName
            ]
        ]);
    }

    public static function createLowStockNotification($ownerId, $productId, $productName, $stock)
    {
        return self::create([
            'user_id' => $ownerId,
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "Product '{$productName}' is running low on stock ({$stock} remaining)",
            'data' => [
                'product_id' => $productId,
                'product_name' => $productName,
                'stock' => $stock
            ]
        ]);
    }
}
