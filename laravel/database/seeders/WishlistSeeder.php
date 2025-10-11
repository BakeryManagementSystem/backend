<?php

namespace Database\Seeders;

use App\Models\Wishlist;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class WishlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::where('user_type', 'buyer')->get();

        if ($buyers->isEmpty()) {
            $this->command->info('No buyers found. Skipping wishlist seeding.');
            return;
        }

        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping wishlist seeding.');
            return;
        }

        foreach ($buyers as $buyer) {
            // Add 2-5 random products to each buyer's wishlist
            $wishlistCount = rand(2, 5);
            $selectedProducts = $products->random(min($wishlistCount, $products->count()));

            foreach ($selectedProducts as $product) {
                Wishlist::updateOrCreate([
                    'user_id' => $buyer->id,
                    'product_id' => $product->id,
                ]);
            }
        }

        $this->command->info('Wishlists seeded successfully!');
    }
}

