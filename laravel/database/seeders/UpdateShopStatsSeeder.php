<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShopProfile;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateShopStatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating shop statistics...');

        $shops = ShopProfile::all();

        foreach ($shops as $shop) {
            // Count products for this shop owner
            $totalProducts = Product::where('owner_id', $shop->owner_id)->count();

            // Calculate average rating from products
            $avgRating = Product::where('owner_id', $shop->owner_id)
                ->whereNotNull('rating')
                ->avg('rating');

            // Update shop profile
            $shop->total_products = $totalProducts;
            $shop->average_rating = $avgRating ? round($avgRating, 2) : 5.0;
            $shop->save();

            $this->command->info("Updated shop: {$shop->shop_name} - Products: {$totalProducts}, Rating: {$shop->average_rating}");
        }

        $this->command->info('Shop statistics updated successfully!');
    }
}

