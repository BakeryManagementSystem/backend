<?php

// Quick test to check review data
// Run this from backend/laravel directory: php test_reviews.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check if reviews exist
$reviewCount = DB::table('product_reviews')->count();
echo "Total reviews in database: {$reviewCount}\n\n";

if ($reviewCount === 0) {
    echo "❌ No reviews found! You need to run the seeder:\n";
    echo "   php artisan db:seed --class=ProductReviewSeeder\n\n";
    exit;
}

// Check products with reviews
$productsWithReviews = DB::table('products')
    ->select('products.*')
    ->join('product_reviews', 'products.id', '=', 'product_reviews.product_id')
    ->groupBy('products.id')
    ->selectRaw('products.*, COUNT(product_reviews.id) as review_count, AVG(product_reviews.rating) as avg_rating')
    ->limit(5)
    ->get();

echo "✅ Sample products with reviews:\n";
echo "--------------------------------\n";
foreach ($productsWithReviews as $product) {
    echo "Product: {$product->name}\n";
    echo "  Reviews: {$product->review_count}\n";
    echo "  Avg Rating: " . round($product->avg_rating, 1) . "\n\n";
}

// Test API endpoint
echo "Testing API endpoint...\n";
$products = DB::table('products')->limit(1)->get();
if ($products->count() > 0) {
    $product = $products->first();

    $reviewsCount = DB::table('product_reviews')
        ->where('product_id', $product->id)
        ->count();

    $averageRating = DB::table('product_reviews')
        ->where('product_id', $product->id)
        ->avg('rating');

    echo "Product ID {$product->id}: {$product->name}\n";
    echo "  reviews_count: {$reviewsCount}\n";
    echo "  average_rating: " . ($averageRating ? round($averageRating, 1) : 0) . "\n";
}

echo "\nIf numbers look good, the backend is working correctly!\n";

