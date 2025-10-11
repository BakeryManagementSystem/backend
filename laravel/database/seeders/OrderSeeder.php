<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get buyer users
        $buyers = User::where('user_type', 'buyer')->get();

        if ($buyers->isEmpty()) {
            $this->command->info('No buyers found. Skipping order seeding.');
            return;
        }

        // Get available products
        $products = Product::where('status', 'active')->where('stock_quantity', '>', 0)->get();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping order seeding.');
            return;
        }

        // Create sample orders
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        foreach ($buyers->take(3) as $buyer) {
            // Create 2-3 orders per buyer
            $orderCount = rand(2, 3);

            for ($i = 0; $i < $orderCount; $i++) {
                // Create order
                $order = Order::create([
                    'buyer_id' => $buyer->id,
                    'status' => $statuses[array_rand($statuses)],
                    'total_amount' => 0, // Will be calculated
                ]);

                // Add 1-4 items to each order
                $itemCount = rand(1, 4);
                $totalAmount = 0;

                for ($j = 0; $j < $itemCount; $j++) {
                    $product = $products->random();
                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;
                    $lineTotal = $unitPrice * $quantity;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'owner_id' => $product->owner_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);

                    $totalAmount += $lineTotal;
                }

                // Update order total
                $order->update(['total_amount' => $totalAmount]);
            }
        }

        $this->command->info('Orders seeded successfully!');
    }
}
