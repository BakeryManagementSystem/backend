<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in order to respect foreign key constraints
        $this->call([
            // 1. Users first (required by many other tables)
            UserSeeder::class,

            // 2. User Profiles (depends on users)
            UserProfileSeeder::class,

            // 3. Categories (required by products)
            CategorySeeder::class,

            // 4. Shop Profiles (depends on users)
            ShopProfileSeeder::class,

            // 5. Ingredients (standalone)
            IngredientSeeder::class,

            // 6. Products (depends on users and categories)
            ProductSeeder::class,

            // 7. Coupons (standalone)
            CouponSeeder::class,

            // 8. User Addresses (depends on users)
            UserAddressSeeder::class,

            // 9. Ingredient Batches (depends on users/owners)
            IngredientBatchSeeder::class,

            // 10. Expenses (depends on users and ingredients)
            ExpenseSeeder::class,

            // 11. Orders and Order Items (depends on users and products)
            OrderSeeder::class,

            // 12. Purchases (depends on orders and order items)
            PurchaseSeeder::class,

            // 13. Sales (depends on users and products)
            SaleSeeder::class,

            // 14. Cart Items (depends on users and products)
            CartItemSeeder::class,

            // 15. Wishlists (depends on users and products)
            WishlistSeeder::class,

            // 16. Product Reviews (depends on users and products)
            ProductReviewSeeder::class,

            // 17. Notifications (depends on users)
            NotificationSeeder::class,
        ]);
    }
}
