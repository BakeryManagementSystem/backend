<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserProfile;
use App\Models\ShopProfile;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // GET /api/me/profile
    public function me(Request $request)
    {
        $u = $request->user();
        $profile = UserProfile::firstOrCreate(
            ['user_id' => $u->id],
            ['name' => $u->name, 'email' => $u->email]
        );
        return response()->json($profile->fresh());
    }

    // POST /api/me/profile (update)
    public function updateMe(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
             'name'         => ['nullable','string','max:255'],   // allow changing user name (optional)
                        'email'        => ['nullable','email','max:255'],    // allow changing user email (optional)
                        'address'      => ['nullable','string','max:255'],
                        'facebook_url' => ['nullable','string','max:255'],
                        'photo'        => ['nullable','image','mimes:jpeg,png,jpg,webp,gif','max:5120'],
        ]);

        $profile = UserProfile::firstOrCreate(
            ['user_id' => $u->id],
            ['name' => $u->name, 'email' => $u->email]
        );


        $dirtyUser = false;
                if (!empty($data['name']) && $data['name'] !== $u->name) {
                    $u->name = $data['name'];
                    $dirtyUser = true;
                }
                if (!empty($data['email']) && $data['email'] !== $u->email) {
                    $u->email = $data['email'];
                    $dirtyUser = true;
                }
                if ($dirtyUser) {
                    $u->save();
                }


        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profiles', 'public');
            $profile->photo_path = $path;
        }
        if (array_key_exists('address', $data)) $profile->address = $data['address'];
        if (array_key_exists('facebook_url', $data)) $profile->facebook_url = $data['facebook_url'];
        // always mirror name/email from user to keep consistent
        $profile->name = $u->name;
        $profile->email = $u->email;

        $profile->save();
        return response()->json(['message' => 'Profile updated', 'profile' => $profile->fresh()]);
    }

    // GET /api/owner/shop (for owners)

    // POST /api/owner/shop (update)
    public function updateShop(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'shop_name' => ['required','string','max:255'],
            'address' => ['nullable','string','max:255'],
            'phone' => ['nullable','string','max:50'],
            'facebook_url' => ['nullable','string','max:255'],
            'logo' => ['nullable','image','mimes:jpeg,png,jpg,webp,gif','max:5120'],
        ]);

        $shop = ShopProfile::firstOrCreate(['owner_id' => $u->id], ['shop_name' => $data['shop_name']]);

        $shop->shop_name = $data['shop_name'];
        if (isset($data['address'])) $shop->address = $data['address'];
        if (isset($data['phone'])) $shop->phone = $data['phone'];
        if (isset($data['facebook_url'])) $shop->facebook_url = $data['facebook_url'];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('shops', 'public');
            $shop->logo_path = $path;
        }

        $shop->save();
        return response()->json(['message' => 'Shop profile updated', 'shop' => $shop]);
    }


    // GET /api/owner/shop  (requires Sanctum token)
   public function myShop(Request $request)
   {
       $u = $request->user();
       $shop = ShopProfile::firstOrCreate(
           ['owner_id' => $u->id],
           ['shop_name' => $u->name . "'s Shop"]
       );

       return response()->json([
           'shop'  => $shop,
           'owner' => [
               'id'    => $u->id,
               'name'  => $u->name,
               'email' => $u->email,
           ],
       ]);
   }


    // GET /api/public/shop/{owner}  (public, no auth required)

    public function publicShop(\App\Models\User $owner)
    {
        $shop = \App\Models\ShopProfile::firstWhere('owner_id', $owner->id);

        return response()->json([
            'shop'  => $shop,
            'owner' => [
                'id'    => $owner->id,
                'name'  => $owner->name,
                'email' => $owner->email,
            ],
        ]);
    }

    // GET /api/shops (public - get all shops with statistics)
    public function allShops(Request $request)
    {
        try {
            $query = ShopProfile::with(['owner' => function($q) {
                $q->select('id', 'name', 'email');
            }]);

            // Apply search filter
            if ($search = $request->get('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('shop_name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Apply filters
            switch ($request->get('filter')) {
                case 'verified':
                    $query->where('verified', true);
                    break;
                case 'top-rated':
                    $query->where('average_rating', '>=', 4.5);
                    break;
                case 'new':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
            }

            // Apply sorting
            switch ($request->get('sort')) {
                case 'rating':
                    $query->orderBy('average_rating', 'desc');
                    break;
                case 'products':
                    $query->orderBy('total_products', 'desc');
                    break;
                case 'reviews':
                    $query->orderBy('total_reviews', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('id', 'desc');
            }

            $shops = $query->get()->map(function($shop) {
                return [
                    'id' => $shop->id,
                    'owner_id' => $shop->owner_id,
                    'shop_name' => $shop->shop_name ?? 'Unnamed Shop',
                    'description' => $shop->description ?? '',
                    'logo_path' => $shop->logo_path ? url(\Storage::url($shop->logo_path)) : null,
                    'banner_path' => $shop->banner_path ? url(\Storage::url($shop->banner_path)) : null,
                    'average_rating' => (float) ($shop->average_rating ?? 5.0),
                    'total_reviews' => (int) ($shop->total_reviews ?? 0),
                    'total_products' => (int) ($shop->total_products ?? 0),
                    'total_sales' => (int) ($shop->total_sales ?? 0),
                    'verified' => (bool) ($shop->verified ?? false),
                    'created_at' => $shop->created_at ? $shop->created_at->toDateTimeString() : null,
                    'owner' => $shop->owner ? [
                        'id' => $shop->owner->id,
                        'name' => $shop->owner->name,
                        'email' => $shop->owner->email
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $shops->values()->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching shops: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
