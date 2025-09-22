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
            'success' => true,
            'data' => [
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
            // Handle both nested and flat data formats
            'theme' => 'nullable|array',
            'theme.primaryColor' => 'nullable|string',
            'theme.secondaryColor' => 'nullable|string',
            'theme.accentColor' => 'nullable|string',
            'primary_color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'accent_color' => 'nullable|string',
            'policies' => 'nullable|array',
            'policies.shipping' => 'nullable|string',
            'policies.returns' => 'nullable|string',
            'policies.exchange' => 'nullable|string',
            'shipping_policy' => 'nullable|string',
            'return_policy' => 'nullable|string',
            'exchange_policy' => 'nullable|string',
            'social' => 'nullable|array',
            'social.website' => 'nullable|url',
            'social.facebook' => 'nullable|string',
            'social.twitter' => 'nullable|string',
            'social.instagram' => 'nullable|string',
            'website' => 'nullable|url',
            'facebook' => 'nullable|string',
            'twitter' => 'nullable|string',
            'instagram' => 'nullable|string',
            'settings' => 'nullable|array',
            'settings.showContactInfo' => 'nullable|boolean',
            'settings.showReviews' => 'nullable|boolean',
            'settings.allowMessages' => 'nullable|boolean',
            'settings.featuredProducts' => 'nullable|array',
            'show_contact_info' => 'nullable|boolean',
            'show_reviews' => 'nullable|boolean',
            'allow_messages' => 'nullable|boolean',
            'featured_products' => 'nullable|array'
        ]);

        $shop = ShopProfile::firstOrCreate(
            ['owner_id' => $user->id],
            ['shop_name' => $data['name']]
        );

        $shop->shop_name = $data['name'];
        $shop->description = $data['description'];

        // Handle theme data (both nested and flat formats)
        if (isset($data['theme'])) {
            $shop->theme = json_encode($data['theme']);
        } else {
            $themeData = [
                'primaryColor' => $data['primary_color'] ?? '#2563eb',
                'secondaryColor' => $data['secondary_color'] ?? '#64748b',
                'accentColor' => $data['accent_color'] ?? '#f59e0b'
            ];
            $shop->theme = json_encode($themeData);
        }

        // Handle policies data (both nested and flat formats)
        if (isset($data['policies'])) {
            $shop->policies = json_encode($data['policies']);
        } else {
            $policiesData = [
                'shipping' => $data['shipping_policy'] ?? '',
                'returns' => $data['return_policy'] ?? '',
                'exchange' => $data['exchange_policy'] ?? ''
            ];
            $shop->policies = json_encode($policiesData);
        }

        // Handle social data (both nested and flat formats)
        if (isset($data['social'])) {
            $shop->social = json_encode($data['social']);
        } else {
            $socialData = [
                'website' => $data['website'] ?? '',
                'facebook' => $data['facebook'] ?? '',
                'twitter' => $data['twitter'] ?? '',
                'instagram' => $data['instagram'] ?? ''
            ];
            $shop->social = json_encode($socialData);
        }

        // Handle settings data (both nested and flat formats)
        if (isset($data['settings'])) {
            $shop->settings = json_encode($data['settings']);
        } else {
            $settingsData = [
                'showContactInfo' => $data['show_contact_info'] ?? true,
                'showReviews' => $data['show_reviews'] ?? true,
                'allowMessages' => $data['allow_messages'] ?? true,
                'featuredProducts' => $data['featured_products'] ?? []
            ];
            $shop->settings = json_encode($settingsData);
        }

        $shop->save();

        return response()->json([
            'success' => true,
            'message' => 'Shop updated successfully',
            'data' => [
                'name' => $shop->shop_name,
                'description' => $shop->description,
                'logo' => $shop->logo_path ? Storage::url($shop->logo_path) : null,
                'banner' => $shop->banner_path ? Storage::url($shop->banner_path) : null,
                'theme' => $shop->theme ? json_decode($shop->theme, true) : null,
                'policies' => $shop->policies ? json_decode($shop->policies, true) : null,
                'social' => $shop->social ? json_decode($shop->social, true) : null,
                'settings' => $shop->settings ? json_decode($shop->settings, true) : null
            ]
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
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'total_views' => (int) $totalViews,
                'total_followers' => (int) $totalFollowers,
                'average_rating' => round($averageRating, 1),
                'total_sales' => $totalSales,
                'monthly_revenue' => round($monthlyRevenue, 2)
            ]
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
            'success' => true,
            'message' => ucfirst($type) . ' uploaded successfully',
            'data' => [
                'url' => Storage::url($path)
            ]
        ]);
    }

    /**
     * Remove shop images (logo/banner)
     */
    public function removeImage(Request $request, $type)
    {
        $user = $request->user();

        $request->validate([
            'type' => 'in:logo,banner'
        ]);

        if (!in_array($type, ['logo', 'banner'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid image type'
            ], 400);
        }

        // Get shop profile
        $shop = ShopProfile::firstOrCreate(
            ['owner_id' => $user->id],
            ['shop_name' => $user->name . "'s Shop"]
        );

        $fieldName = $type === 'logo' ? 'logo_path' : 'banner_path';
        $oldPath = $shop->$fieldName;

        // Delete old image if exists
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        // Clear the field
        $shop->$fieldName = null;
        $shop->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' removed successfully'
        ]);
    }
}
