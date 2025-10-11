<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:buyer,seller,owner',
            'shop_name' => 'nullable|string|max:255'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'shop_name' => $request->shop_name ?? $request->name . "'s Shop",
        ]);

        // Create shop profile for sellers/owners
        if (in_array($request->user_type, ['seller', 'owner'])) {
            \App\Models\ShopProfile::create([
                'owner_id' => $user->id,
                'shop_name' => $request->shop_name ?? $request->name . "'s Shop",
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
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
