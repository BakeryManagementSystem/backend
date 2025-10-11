<?php

namespace Database\Seeders;

use App\Models\ProductReview;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting product reviews seeding...');

        // Get all buyers
        $buyers = User::where('user_type', 'buyer')->get();

        if ($buyers->isEmpty()) {
            $this->command->warn('No buyers found. Skipping product reviews seeding.');
            return;
        }

        // Get all products
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Skipping product reviews seeding.');
            return;
        }

        // Get delivered orders
        $deliveredOrders = Order::where('status', 'delivered')->with('items')->get();

        $this->command->info("Found {$buyers->count()} buyers, {$products->count()} products, and {$deliveredOrders->count()} delivered orders.");

        // Comprehensive review texts with ratings
        $reviewTemplates = [
            5 => [
                'Absolutely amazing! The quality exceeded my expectations. Fresh, delicious, and perfectly made.',
                'Outstanding product! The taste is incredible and the presentation is beautiful. Will definitely order again!',
                'Best bakery item I\'ve ever had! The texture is perfect and the flavor is divine. Highly recommended!',
                'Exceptional quality! Fresh ingredients and you can really taste the difference. Worth every penny!',
                'Perfect in every way! Delivered fresh and tasted amazing. My family loved it!',
                'Couldn\'t be happier with this purchase! The quality is top-notch and it arrived fresh.',
                'Simply delicious! This has become a regular order for our family. Outstanding taste!',
                'Wow! Exceeded all expectations. Fresh, flavorful, and beautifully presented. 5 stars!',
                'The best! Fresh ingredients, perfect texture, and amazing taste. Will order again and again!',
                'Absolutely love it! Quality is consistent every time. My go-to bakery product!',
            ],
            4 => [
                'Very good product! Fresh and tasty. Only minor improvement would be packaging.',
                'Great quality and taste! Arrived fresh and well-packaged. Would definitely recommend.',
                'Really enjoyed this! Good portion size and fresh ingredients. Will order again.',
                'Excellent product overall. Taste is great and quality is consistent. Very satisfied!',
                'Very pleased with this purchase. Fresh, delicious, and good value for money.',
                'Good quality product. Tastes fresh and homemade. Slightly expensive but worth it.',
                'Really good! The flavor is excellent and it stayed fresh for days. Happy customer!',
                'Impressive quality! Tastes just like homemade. Only wish it came in larger sizes.',
                'Very tasty and fresh! Good ingredients and well-made. Would buy again.',
                'Great product! Arrived fresh and tasted wonderful. Minor improvements possible but overall very good.',
            ],
            3 => [
                'Decent product. Taste is okay but nothing exceptional. Adequate quality.',
                'It\'s good, meets basic expectations. Fresh enough but could have more flavor.',
                'Average product. Does the job but not outstanding. Reasonably priced though.',
                'Okay product. Fresh when arrived but taste was just average. Not bad, not great.',
                'Fair quality. Texture is fine but lacks that special something. Acceptable purchase.',
                'Satisfactory. Got what I expected, nothing more, nothing less. Would try other options.',
                'Decent but room for improvement. Fresh ingredients but flavor could be better.',
                'It\'s alright. Does what it\'s supposed to do. Might try different variety next time.',
                'Acceptable quality. Nothing wrong with it but didn\'t wow me either. Fair price.',
                'Good enough. Met basic expectations but wouldn\'t say it\'s my favorite. Average overall.',
            ],
        ];

        $reviewsCreated = 0;
        $reviewsSkipped = 0;

        // Strategy 1: Create reviews for delivered orders (most realistic)
        foreach ($deliveredOrders as $order) {
            if (!$order->items || $order->items->isEmpty()) {
                continue;
            }

            // 70% chance this order will have reviews
            if (rand(1, 100) > 70) {
                continue;
            }

            foreach ($order->items as $item) {
                // 60% chance each product in the order gets reviewed
                if (rand(1, 100) > 60) {
                    continue;
                }

                // Check if review already exists
                $existingReview = ProductReview::where('user_id', $order->buyer_id)
                    ->where('product_id', $item->product_id)
                    ->where('order_id', $order->id)
                    ->first();

                if ($existingReview) {
                    $reviewsSkipped++;
                    continue;
                }

                // Generate realistic rating (skewed towards positive)
                $rating = $this->generateRealisticRating();

                // Get appropriate review text
                $reviewText = $reviewTemplates[$rating][array_rand($reviewTemplates[$rating])];

                // 30% chance of shorter review (rating only)
                if (rand(1, 100) <= 30) {
                    $reviewText = null;
                }

                ProductReview::create([
                    'user_id' => $order->buyer_id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'review' => $reviewText,
                    'is_verified_purchase' => true, // Always true for order-based reviews
                    'created_at' => $order->updated_at->addDays(rand(1, 7)), // Review 1-7 days after order
                ]);

                $reviewsCreated++;
            }
        }

        // Strategy 2: Add some standalone reviews (not linked to orders) for products
        // This simulates reviews from older orders or non-tracked purchases
        foreach ($products as $product) {
            // Each product gets 1-4 additional reviews
            $additionalReviews = rand(1, 4);

            for ($i = 0; $i < $additionalReviews; $i++) {
                $randomBuyer = $buyers->random();

                // Check if review already exists (without order_id)
                $existingReview = ProductReview::where('user_id', $randomBuyer->id)
                    ->where('product_id', $product->id)
                    ->whereNull('order_id')
                    ->first();

                if ($existingReview) {
                    $reviewsSkipped++;
                    continue;
                }

                $rating = $this->generateRealisticRating();
                $reviewText = $reviewTemplates[$rating][array_rand($reviewTemplates[$rating])];

                // 20% chance of no review text
                if (rand(1, 100) <= 20) {
                    $reviewText = null;
                }

                ProductReview::create([
                    'user_id' => $randomBuyer->id,
                    'product_id' => $product->id,
                    'order_id' => null,
                    'rating' => $rating,
                    'review' => $reviewText,
                    'is_verified_purchase' => false, // Not verified since no order link
                    'created_at' => now()->subDays(rand(7, 90)), // Random date in past 90 days
                ]);

                $reviewsCreated++;
            }
        }

        $this->command->info("✅ Created {$reviewsCreated} reviews");
        if ($reviewsSkipped > 0) {
            $this->command->info("⏭️  Skipped {$reviewsSkipped} duplicate reviews");
        }
        $this->command->info('Product reviews seeded successfully!');
    }

    /**
     * Generate a realistic rating (skewed towards positive)
     * Real-world data shows most reviews are 4-5 stars
     */
    private function generateRealisticRating(): int
    {
        $rand = rand(1, 100);

        if ($rand <= 50) {
            return 5; // 50% - 5 stars
        } elseif ($rand <= 80) {
            return 4; // 30% - 4 stars
        } else {
            return 3; // 20% - 3 stars
        }
    }
}
