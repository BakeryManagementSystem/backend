<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\ShopProfile;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        // Log incoming request data for debugging
        \Log::info('Registration request data:', $req->all());

        $data = $req->validate([
            'name'          => ['required','string','max:255'],
            'email'         => ['required','email','max:255','unique:users,email'],
            'password'      => ['required','string','min:8'],
            'user_type'     => ['required','string','in:buyer,seller,owner'],
            'phone'         => ['nullable','string','max:20'],
            'date_of_birth' => ['nullable','date'],
            'shop_name'     => ['nullable','string','max:255']
        ]);

        // Log validated data
        \Log::info('Validated data:', $data);

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->user_type = $data['user_type'];
        $user->phone = $data['phone'] ?? null;
        $user->date_of_birth = $data['date_of_birth'] ?? null;
        $user->shop_name = $data['shop_name'] ?? null;
        $user->save();

        // Log what was actually saved
        \Log::info('User saved with date_of_birth:', ['id' => $user->id, 'date_of_birth' => $user->date_of_birth]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        // Create shop profile for sellers/owners
        if (in_array($data['user_type'], ['seller', 'owner'])) {
            ShopProfile::create([
                'owner_id' => $user->id,
                'shop_name' => $data['shop_name'] ?? $user->name . "'s Shop",
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

        // Create token for auto-login after registration
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }


    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
      $token = $user->createToken('api-token')->plainTextToken;


        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );


        return response()->json([
            'message' => 'Login ok',
            'user'    => $user,
            'token'   => $token,
        ], 200);
    }

     public function logout(Request $request)
        {
            // revoke current token
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out']);
        }
}
