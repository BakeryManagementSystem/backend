<?php

namespace Database\Seeders;

use App\Models\ProductReview;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::where('user_type', 'buyer')->get();

        if ($buyers->isEmpty()) {
            $this->command->info('No buyers found. Skipping product reviews seeding.');
            return;
        }

        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping product reviews seeding.');
            return;
        }

        $reviewTexts = [
            'Excellent product! Highly recommend.',
            'Very good quality. Will buy again.',
            'Amazing taste and freshness!',
            'Good value for money.',
            'Fresh and delicious as always.',
            'Best bakery items I\'ve tried!',
            'Decent product, could be better.',
            'Love it! My family enjoys it too.',
            'Fresh ingredients, great flavor.',
            'Satisfactory. Met my expectations.',
        ];

        foreach ($buyers as $buyer) {
            // Add 1-3 reviews per buyer
            $reviewCount = rand(1, 3);
            $selectedProducts = $products->random(min($reviewCount, $products->count()));

            foreach ($selectedProducts as $product) {
                // Check if review already exists
                if (ProductReview::where('user_id', $buyer->id)->where('product_id', $product->id)->exists()) {
                    continue;
                }

                ProductReview::create([
                    'user_id' => $buyer->id,
                    'product_id' => $product->id,
                    'order_id' => null,
                    'rating' => rand(3, 5),
                    'review' => $reviewTexts[array_rand($reviewTexts)],
                    'is_verified_purchase' => (bool)rand(0, 1),
                ]);
            }
        }

        $this->command->info('Product reviews seeded successfully!');
    }
}

