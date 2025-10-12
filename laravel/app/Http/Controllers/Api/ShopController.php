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

        $shop = ShopProfile::where('owner_id', $user->id)->first();

        if (!$shop) {
            $shop = ShopProfile::create([
                'owner_id' => $user->id,
                'shop_name' => $user->name . "'s Shop",
                'description' => '',
                'theme' => [
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#64748b',
                    'accentColor' => '#f59e0b'
                ],
                'policies' => [
                    'shipping' => '',
                    'returns' => '',
                    'exchange' => ''
                ],
                'social' => [
                    'website' => '',
                    'facebook' => '',
                    'twitter' => '',
                    'instagram' => ''
                ],
                'settings' => [
                    'showContactInfo' => true,
                    'showReviews' => true,
                    'allowMessages' => true,
                    'featuredProducts' => []
                ]
            ]);
        }

        // Refresh from database to ensure we have latest data
        $shop->refresh();

        \Log::info('Getting shop data', [
            'shop_id' => $shop->id,
            'logo_path' => $shop->logo_path,
            'banner_path' => $shop->banner_path,
            'theme' => $shop->theme
        ]);

        // Helper function to get proper URL
        $getImageUrl = function($path) {
            if (!$path) return null;
            // Check if it's already a full URL (http:// or https://)
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            // Otherwise, it's a local storage path
            return Storage::url($path);
        };

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $shop->id,
                'owner_id' => $shop->owner_id,
                'name' => $shop->shop_name,
                'description' => $shop->description ?? '',
                'logo' => $getImageUrl($shop->logo_path),
                'banner' => $getImageUrl($shop->banner_path),
                'logo_path' => $getImageUrl($shop->logo_path), // Keep for backward compatibility
                'banner_path' => $getImageUrl($shop->banner_path), // Keep for backward compatibility
                'theme' => $shop->theme ?? [
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#64748b',
                    'accentColor' => '#f59e0b'
                ],
                'policies' => $shop->policies ?? [
                    'shipping' => '',
                    'returns' => '',
                    'exchange' => ''
                ],
                'social' => $shop->social ?? [
                    'website' => '',
                    'facebook' => '',
                    'twitter' => '',
                    'instagram' => ''
                ],
                'settings' => $shop->settings ?? [
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

        \Log::info('Shop update request received', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

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

        // Handle logo and banner URLs
        if (isset($data['logo'])) {
            $shop->logo_path = $data['logo'];
        }
        if (isset($data['banner'])) {
            $shop->banner_path = $data['banner'];
        }

        // Handle theme data (both nested and flat formats)
        if (isset($data['theme'])) {
            $shop->theme = $data['theme'];
        } else {
            $shop->theme = [
                'primaryColor' => $data['primary_color'] ?? '#2563eb',
                'secondaryColor' => $data['secondary_color'] ?? '#64748b',
                'accentColor' => $data['accent_color'] ?? '#f59e0b'
            ];
        }

        \Log::info('Theme data being saved', [
            'theme' => $shop->theme
        ]);

        // Handle policies data (both nested and flat formats)
        if (isset($data['policies'])) {
            $shop->policies = $data['policies'];
        } else {
            $shop->policies = [
                'shipping' => $data['shipping_policy'] ?? '',
                'returns' => $data['return_policy'] ?? '',
                'exchange' => $data['exchange_policy'] ?? ''
            ];
        }

        // Handle social data (both nested and flat formats)
        if (isset($data['social'])) {
            $shop->social = $data['social'];
        } else {
            $shop->social = [
                'website' => $data['website'] ?? '',
                'facebook' => $data['facebook'] ?? '',
                'twitter' => $data['twitter'] ?? '',
                'instagram' => $data['instagram'] ?? ''
            ];
        }

        // Handle settings data (both nested and flat formats)
        if (isset($data['settings'])) {
            $shop->settings = $data['settings'];
        } else {
            $shop->settings = [
                'showContactInfo' => $data['show_contact_info'] ?? true,
                'showReviews' => $data['show_reviews'] ?? true,
                'allowMessages' => $data['allow_messages'] ?? true,
                'featuredProducts' => $data['featured_products'] ?? []
            ];
        }

        $shop->save();

        \Log::info('Shop saved successfully', [
            'shop_id' => $shop->id,
            'theme_in_db' => $shop->fresh()->theme
        ]);

        // Helper function to get proper URL
        $getImageUrl = function($path) {
            if (!$path) return null;
            // Check if it's already a full URL (http:// or https://)
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            // Otherwise, it's a local storage path
            return Storage::url($path);
        };

        return response()->json([
            'success' => true,
            'message' => 'Shop updated successfully',
            'data' => [
                'name' => $shop->shop_name,
                'description' => $shop->description,
                'logo' => $getImageUrl($shop->logo_path),
                'banner' => $getImageUrl($shop->banner_path),
                'theme' => $shop->theme,
                'policies' => $shop->policies,
                'social' => $shop->social,
                'settings' => $shop->settings
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

        // Get orders statistics
        $orders = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        });

        $totalSales = $orders->count();
        $monthlyRevenue = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->with(['orderItems' => function($query) use ($user) {
            $query->where('owner_id', $user->id);
        }])
        ->get()
        ->sum(function($order) use ($user) {
            return $order->orderItems->where('owner_id', $user->id)->sum('line_total');
        });

        // Calculate average rating (if we have reviews)
        $averageRating = 4.5; // Default since no rating system yet

        // For now, we'll use some mock data for views and followers
        $totalViews = $totalProducts * 150; // Estimate based on products
        $totalFollowers = max(0, $totalSales * 0.1); // Estimate based on sales

        // Get customer geography data (mock data based on orders)
        $customerGeography = Order::whereHas('orderItems', function($query) use ($user) {
            $query->where('owner_id', $user->id);
        })
        ->with('buyer')
        ->get()
        ->groupBy(function($order) {
            // Mock geography based on user email domain or random assignment
            $domains = ['USA', 'Canada', 'UK', 'Australia', 'Germany', 'France', 'Other'];
            return $domains[array_rand($domains)];
        })
        ->map(function($orders, $location) use ($totalSales) {
            return [
                'location' => $location,
                'customers' => $orders->unique('buyer_id')->count(),
                'orders' => $orders->count(),
                'percentage' => $totalSales > 0 ? round(($orders->count() / $totalSales) * 100, 1) : 0
            ];
        })
        ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'total_views' => (int) $totalViews,
                'total_followers' => (int) $totalFollowers,
                'average_rating' => round($averageRating, 1),
                'total_sales' => $totalSales,
                'monthly_revenue' => round($monthlyRevenue, 2),
                'customer_geography' => $customerGeography
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
