<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        $data = $req->validate([
            'name'      => ['required','string','max:255'],
            'email'     => ['required','email','max:255','unique:users,email'],
            'password'  => ['required','string','min:8'],
            'user_type' => ['required','string','in:buyer,seller,owner'] // adjust as needed
        ]);

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->user_type = $data['user_type'];
        $user->save();

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name, 'email' => $user->email]
        );

        return response()->json(['message' => 'User registered successfully'], 201);
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
