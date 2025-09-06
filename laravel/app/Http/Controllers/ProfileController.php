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
        return response()->json($profile);
    }

    // POST /api/me/profile (update)
    public function updateMe(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'address' => ['nullable','string','max:255'],
            'facebook_url' => ['nullable','string','max:255'],
            'photo' => ['nullable','image','mimes:jpeg,png,jpg,webp,gif','max:5120'],
        ]);

        $profile = UserProfile::firstOrCreate(
            ['user_id' => $u->id],
            ['name' => $u->name, 'email' => $u->email]
        );

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profiles', 'public');
            $profile->photo_path = $path;
        }
        if (isset($data['address'])) $profile->address = $data['address'];
        if (isset($data['facebook_url'])) $profile->facebook_url = $data['facebook_url'];
        // always mirror name/email from user to keep consistent
        $profile->name = $u->name;
        $profile->email = $u->email;

        $profile->save();
        return response()->json(['message' => 'Profile updated', 'profile' => $profile]);
    }

    // GET /api/owner/shop (for owners)
    public function myShop(Request $request)
    {
        $u = $request->user();
        $shop = ShopProfile::firstOrCreate(
            ['owner_id' => $u->id],
            ['shop_name' => $u->name . "'s Shop"]
        );
        return response()->json($shop);
    }

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
}
