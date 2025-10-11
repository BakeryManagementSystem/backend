<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShopProfile;

class ShopProfileSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $shopProfiles = [
            [
                'owner_id' => 2, // Baker Shop Owner
                'shop_name' => 'The Artisan Bakery',
                'description' => 'A wonderful local bakery serving fresh, delicious baked goods daily.',
                'address' => '123 Main Street, Downtown',
                'phone' => '+1234567891',
                'logo_path' => null,
                'banner_path' => null,
                'facebook_url' => 'https://facebook.com/artisanbakery',
                'theme' => json_encode([
                    'primary_color' => '#ff6b9d',
                    'secondary_color' => '#c44569',
                    'accent_color' => '#f8b500'
                ]),
                'policies' => json_encode([
                    'return_policy' => '24-hour return policy for fresh items',
                    'delivery_policy' => 'Free delivery within 5 miles',
                    'custom_orders' => 'Custom orders require 48-hour notice'
                ]),
                'social' => json_encode([
                    'facebook' => 'https://facebook.com/artisanbakery',
                    'instagram' => '@artisanbakery',
                    'website' => 'www.artisanbakery.com'
                ]),
                'settings' => json_encode([
                    'pickup_available' => true,
                    'delivery_available' => true,
                    'accepts_custom_orders' => true
                ]),
            ],
            [
                'owner_id' => 3, // Sweet Delights Owner
                'shop_name' => 'Sweet Delights',
                'description' => 'Premium cakes and pastries for all occasions.',
                'address' => '456 Oak Avenue, Uptown',
                'phone' => '+1234567892',
                'logo_path' => null,
                'banner_path' => null,
                'facebook_url' => 'https://facebook.com/sweetdelights',
                'theme' => json_encode([
                    'primary_color' => '#ffd700',
                    'secondary_color' => '#ff8c00',
                    'accent_color' => '#dc143c'
                ]),
                'policies' => json_encode([
                    'return_policy' => 'Fresh guarantee - replace within 24 hours',
                    'delivery_policy' => 'Same-day delivery available',
                    'custom_orders' => 'Wedding cakes and special event desserts'
                ]),
                'social' => json_encode([
                    'facebook' => 'https://facebook.com/sweetdelights',
                    'instagram' => '@sweetdelights',
                    'website' => 'www.sweetdelights.com'
                ]),
                'settings' => json_encode([
                    'pickup_available' => true,
                    'delivery_available' => true,
                    'accepts_custom_orders' => true
                ]),
            ],
        ];

        foreach ($shopProfiles as $profile) {
            ShopProfile::updateOrCreate(
                ['owner_id' => $profile['owner_id']],
                $profile
            );
        }
    }
}

