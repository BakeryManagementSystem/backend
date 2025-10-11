<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get completed orders
        $orders = Order::where('status', 'completed')->with('orderItems')->get();

        if ($orders->isEmpty()) {
            $this->command->info('No completed orders found. Skipping purchases seeding.');
            return;
        }

        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                // Create purchase record for each order item
                Purchase::create([
                    'owner_id' => $item->owner_id,
                    'buyer_id' => $order->buyer_id,
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'sold_at' => $order->created_at ?? now(),
                ]);
            }
        }

        $this->command->info('Purchases seeded successfully!');
    }
}

