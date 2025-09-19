<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShopProfile;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    /**
     * Get shop profile for authenticated owner
     */
    public function getShop(Request $request)
    {
        $user = $request->user();

        $shop = ShopProfile::firstOrCreate(
            ['owner_id' => $user->id],
            [
                'shop_name' => $user->name . "'s Shop",
                'description' => '',
                'theme' => json_encode([
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#64748b',
                    'accentColor' => '#f59e0b'
                ]),
                'policies' => json_encode([
                    'shipping' => '',
                    'returns' => '',
                    'exchange' => ''
                ]),
                'social' => json_encode([
                    'website' => '',
                    'facebook' => '',
                    'twitter' => '',
                    'instagram' => ''
                ]),
                'settings' => json_encode([
                    'showContactInfo' => true,
                    'showReviews' => true,
                    'allowMessages' => true,
                    'featuredProducts' => []
                ])
            ]
        );

        return response()->json([
            'name' => $shop->shop_name,
            'description' => $shop->description ?? '',
            'logo' => $shop->logo_path ? Storage::url($shop->logo_path) : null,
            'banner' => $shop->banner_path ? Storage::url($shop->banner_path) : null,
            'theme' => $shop->theme ? json_decode($shop->theme, true) : [
                'primaryColor' => '#2563eb',
                'secondaryColor' => '#64748b',
                'accentColor' => '#f59e0b'
            ],
            'policies' => $shop->policies ? json_decode($shop->policies, true) : [
                'shipping' => '',
                'returns' => '',
                'exchange' => ''
            ],
            'social' => $shop->social ? json_decode($shop->social, true) : [
                'website' => '',
                'facebook' => '',
                'twitter' => '',
                'instagram' => ''
            ],
            'settings' => $shop->settings ? json_decode($shop->settings, true) : [
                'showContactInfo' => true,
                'showReviews' => true,
                'allowMessages' => true,
                'featuredProducts' => []
            ]
        ]);
    }

    /**
     * Update shop profile
     */
    public function updateShop(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|min:50|max:500',
            'logo' => 'nullable|string',
            'banner' => 'nullable|string',
            'theme' => 'nullable|array',
            'theme.primaryColor' => 'nullable|string',
            'theme.secondaryColor' => 'nullable|string',
            'theme.accentColor' => 'nullable|string',
            'policies' => 'nullable|array',
            'policies.shipping' => 'nullable|string',
            'policies.returns' => 'nullable|string',
            'policies.exchange' => 'nullable|string',
            'social' => 'nullable|array',
            'social.website' => 'nullable|url',
            'social.facebook' => 'nullable|string',
            'social.twitter' => 'nullable|string',
            'social.instagram' => 'nullable|string',
            'settings' => 'nullable|array',
            'settings.showContactInfo' => 'nullable|boolean',
            'settings.showReviews' => 'nullable|boolean',
            'settings.allowMessages' => 'nullable|boolean',
            'settings.featuredProducts' => 'nullable|array'
        ]);

        $shop = ShopProfile::firstOrCreate(
            ['owner_id' => $user->id],
            ['shop_name' => $data['name']]
        );

        $shop->shop_name = $data['name'];
        $shop->description = $data['description'];

        if (isset($data['theme'])) {
            $shop->theme = json_encode($data['theme']);
        }

        if (isset($data['policies'])) {
            $shop->policies = json_encode($data['policies']);
        }

        if (isset($data['social'])) {
            $shop->social = json_encode($data['social']);
        }

        if (isset($data['settings'])) {
            $shop->settings = json_encode($data['settings']);
        }

        $shop->save();

        return response()->json([
            'message' => 'Shop updated successfully',
            'shop' => $shop
        ]);
    }

    /**
     * Get shop statistics
     */
    public function getShopStats(Request $request)
    {
        $user = $request->user();

        // Get products count
        $totalProducts = Product::where('owner_id', $user->id)->count();

        // Get orders statistics (assuming we have orders table)
        $orders = Order::whereHas('items.product', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        });

        $totalSales = $orders->count();
        $monthlyRevenue = $orders->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->sum('total_amount');

        // Calculate average rating (if we have reviews)
        $averageRating = DB::table('products')
            ->where('owner_id', $user->id)
            ->avg('rating') ?? 0;

        // For now, we'll use some mock data for views and followers
        $totalViews = $totalProducts * 150; // Estimate based on products
        $totalFollowers = max(0, $totalSales * 0.1); // Estimate based on sales

        return response()->json([
            'totalProducts' => $totalProducts,
            'totalViews' => (int) $totalViews,
            'totalFollowers' => (int) $totalFollowers,
            'averageRating' => round($averageRating, 1),
            'totalSales' => $totalSales,
            'monthlyRevenue' => round($monthlyRevenue, 2)
        ]);
    }

    /**
     * Upload shop images (logo/banner)
     */
    public function uploadImage(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'type' => 'required|in:logo,banner',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120' // 5MB max
        ]);

        $type = $request->input('type');
        $image = $request->file('image');

        // Store the image
        $path = $image->store('shops/' . $type, 'public');

        // Update shop profile
        $shop = ShopProfile::firstOrCreate(
            ['owner_id' => $user->id],
            ['shop_name' => $user->name . "'s Shop"]
        );

        if ($type === 'logo') {
            // Delete old logo if exists
            if ($shop->logo_path) {
                Storage::disk('public')->delete($shop->logo_path);
            }
            $shop->logo_path = $path;
        } else {
            // Delete old banner if exists
            if ($shop->banner_path) {
                Storage::disk('public')->delete($shop->banner_path);
            }
            $shop->banner_path = $path;
        }

        $shop->save();

        return response()->json([
            'message' => ucfirst($type) . ' uploaded successfully',
            'url' => Storage::url($path)
        ]);
    }
}
