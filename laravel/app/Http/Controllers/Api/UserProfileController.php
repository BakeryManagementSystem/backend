<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        // Get user's default address
        $defaultAddress = UserAddress::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'phone' => $user->phone ?? '',
            'date_of_birth' => $user->date_of_birth ?? '',
            'avatar' => $user->avatar ?? '',
            'address' => $defaultAddress ? [
                'id' => $defaultAddress->id,
                'street' => $defaultAddress->address_line_1,
                'city' => $defaultAddress->city,
                'state' => $defaultAddress->state,
                'zipCode' => $defaultAddress->postal_code,
                'country' => $defaultAddress->country
            ] : null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Log incoming request data
        \Log::info('Profile update request data:', $request->all());

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'avatar' => 'nullable|string',
            'address' => 'nullable|array',
            'address.street' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.zipCode' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100'
        ]);

        // Log validated data
        \Log::info('Validated profile data:', $validatedData);

        // Update user information
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'avatar' => $request->avatar
        ]);

        // Log what was saved
        $user->refresh();
        \Log::info('User after update:', ['id' => $user->id, 'date_of_birth' => $user->date_of_birth, 'phone' => $user->phone]);

        // Update or create address
        if ($request->address) {
            UserAddress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'shipping',
                    'is_default' => true
                ],
                [
                    'first_name' => explode(' ', $user->name)[0] ?? '',
                    'last_name' => explode(' ', $user->name, 2)[1] ?? '',
                    'address_line_1' => $request->address['street'] ?? '',
                    'city' => $request->address['city'] ?? '',
                    'state' => $request->address['state'] ?? '',
                    'postal_code' => $request->address['zipCode'] ?? '',
                    'country' => $request->address['country'] ?? 'US'
                ]
            );
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $this->show()->getData()
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    public function getAddresses()
    {
        $addresses = UserAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($addresses);
    }

    public function updateAddress(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:shipping,billing',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean'
        ]);

        $address = UserAddress::where('user_id', Auth::id())->findOrFail($id);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            UserAddress::where('user_id', Auth::id())
                ->where('type', $request->type)
                ->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address
        ]);
    }
}
