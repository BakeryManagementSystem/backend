<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $products = [
            // Products for Baker Shop Owner (owner_id = 2)
            [
                'owner_id' => 2,
                'name' => 'Sourdough Bread Loaf',
                'description' => 'Traditional sourdough with a crispy crust and tangy flavor. Made with our 100-year-old starter.',
                'price' => 8.50,
                'discount_price' => null,
                'category' => 'Breads',
                'category_id' => 1,
                'stock_quantity' => 20,
                'sku' => 'BREAD-SD-001',
                'weight' => 0.75,
                'dimensions' => '30x15x12cm',
                'ingredients' => json_encode(['flour', 'water', 'salt', 'sourdough starter']),
                'allergens' => json_encode(['gluten']),
                'status' => 'active',
                'rating' => 4.80,
                'rating_count' => 25,
                'is_featured' => true,
                'meta_title' => 'Artisan Sourdough Bread',
                'meta_description' => 'Handcrafted sourdough bread baked fresh daily',
                'image_path' => null,
                'images' => json_encode([]),
            ],
            [
                'owner_id' => 2,
                'name' => 'French Baguette',
                'description' => 'Authentic French baguette with a golden crust and airy interior. Perfect for sandwiches or with cheese.',
                'price' => 3.25,
                'discount_price' => null,
                'category' => 'Breads',
                'category_id' => 1,
                'stock_quantity' => 30,
                'sku' => 'BREAD-BAG-001',
                'weight' => 0.25,
                'dimensions' => '60x8x8cm',
                'ingredients' => json_encode(['flour', 'water', 'salt', 'yeast']),
                'allergens' => json_encode(['gluten']),
                'status' => 'active',
                'rating' => 4.65,
                'rating_count' => 18,
                'is_featured' => false,
                'meta_title' => 'Classic French Baguette',
                'meta_description' => 'Traditional French baguette baked fresh',
                'image_path' => null,
                'images' => json_encode([]),
            ],
            [
                'owner_id' => 2,
                'name' => 'Chocolate Chip Cookies (Dozen)',
                'description' => 'Classic chocolate chip cookies with a crispy edge and chewy center. Made with premium chocolate chips.',
                'price' => 12.00,
                'discount_price' => 10.50,
                'category' => 'Cookies',
                'category_id' => 4,
                'stock_quantity' => 50,
                'sku' => 'COOKIE-CC-001',
                'weight' => 0.40,
                'dimensions' => '25x20x5cm',
                'ingredients' => json_encode(['flour', 'butter', 'sugar', 'eggs', 'chocolate chips', 'vanilla']),
                'allergens' => json_encode(['gluten', 'dairy', 'eggs']),
                'status' => 'active',
                'rating' => 4.90,
                'rating_count' => 42,
                'is_featured' => true,
                'meta_title' => 'Homemade Chocolate Chip Cookies',
                'meta_description' => 'Fresh baked chocolate chip cookies',
                'image_path' => null,
                'images' => json_encode([]),
            ],

            // Products for Sweet Delights Owner (owner_id = 3)
            [
                'owner_id' => 3,
                'name' => 'Red Velvet Cake',
                'description' => 'Our signature red velvet cake with cream cheese frosting. A southern classic with a modern twist.',
                'price' => 35.00,
                'discount_price' => null,
                'category' => 'Cakes',
                'category_id' => 3,
                'stock_quantity' => 5,
                'sku' => 'CAKE-RV-001',
                'weight' => 1.20,
                'dimensions' => '20x20x10cm',
                'ingredients' => json_encode(['flour', 'sugar', 'eggs', 'buttermilk', 'cocoa', 'cream cheese', 'butter']),
                'allergens' => json_encode(['gluten', 'dairy', 'eggs']),
                'status' => 'active',
                'rating' => 4.95,
                'rating_count' => 67,
                'is_featured' => true,
                'meta_title' => 'Classic Red Velvet Cake',
                'meta_description' => 'Delicious red velvet cake with cream cheese frosting',
                'image_path' => null,
                'images' => json_encode([]),
            ],
            [
                'owner_id' => 3,
                'name' => 'Chocolate Cupcakes (Box of 6)',
                'description' => 'Rich, moist chocolate cupcakes topped with silky chocolate buttercream frosting. Made with premium Belgian cocoa.',
                'price' => 18.00,
                'discount_price' => 15.99,
                'category' => 'Desserts',
                'category_id' => 5,
                'stock_quantity' => 25,
                'sku' => 'CUP-CHOC-001',
                'weight' => 0.50,
                'dimensions' => '20x15x8cm',
                'ingredients' => json_encode(['flour', 'sugar', 'cocoa', 'eggs', 'butter', 'milk']),
                'allergens' => json_encode(['gluten', 'dairy', 'eggs']),
                'status' => 'active',
                'rating' => 4.75,
                'rating_count' => 31,
                'is_featured' => false,
                'meta_title' => 'Premium Chocolate Cupcakes',
                'meta_description' => 'Decadent chocolate cupcakes',
                'image_path' => null,
                'images' => json_encode([]),
            ],
            [
                'owner_id' => 3,
                'name' => 'Blueberry Muffins (Box of 4)',
                'description' => 'Fluffy muffins bursting with fresh blueberries and a hint of lemon zest.',
                'price' => 14.00,
                'discount_price' => null,
                'category' => 'Desserts',
                'category_id' => 5,
                'stock_quantity' => 30,
                'sku' => 'MUFF-BLUE-001',
                'weight' => 0.35,
                'dimensions' => '18x12x8cm',
                'ingredients' => json_encode(['flour', 'sugar', 'eggs', 'butter', 'blueberries', 'lemon zest']),
                'allergens' => json_encode(['gluten', 'dairy', 'eggs']),
                'status' => 'active',
                'rating' => 4.60,
                'rating_count' => 22,
                'is_featured' => false,
                'meta_title' => 'Fresh Blueberry Muffins',
                'meta_description' => 'Delicious blueberry muffins baked daily',
                'image_path' => null,
                'images' => json_encode([]),
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}

