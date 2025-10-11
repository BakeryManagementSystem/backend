<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CartItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get buyer users
        $buyers = User::where('user_type', 'buyer')->get();

        if ($buyers->isEmpty()) {
            $this->command->info('No buyers found. Skipping cart items seeding.');
            return;
        }

        // Get available products
        $products = Product::where('status', 'active')->where('stock_quantity', '>', 0)->get();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping cart items seeding.');
            return;
        }

        // Add items to cart for some buyers
        foreach ($buyers->take(3) as $buyer) {
            // Add 1-3 items to each buyer's cart
            $itemCount = rand(1, 3);
            $addedProducts = [];

            for ($i = 0; $i < $itemCount; $i++) {
                // Get a product that hasn't been added yet for this buyer
                $product = $products->whereNotIn('id', $addedProducts)->random();
                $addedProducts[] = $product->id;

                CartItem::create([
                    'user_id' => $buyer->id,
                    'product_id' => $product->id,
                    'quantity' => rand(1, 5),
                    'unit_price' => $product->price,
                ]);
            }
        }

        $this->command->info('Cart items seeded successfully!');
    }
}
