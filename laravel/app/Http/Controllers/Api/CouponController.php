<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    /**
     * Get all coupons for the authenticated seller
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Coupon::where('seller_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 15);
        $coupons = $query->paginate($perPage);

        return response()->json($coupons);
    }

    /**
     * Get active/public coupons (for buyers)
     */
    public function getPublicCoupons(Request $request)
    {
        $coupons = Coupon::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where(function($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('usage_count < usage_limit');
            })
            ->get();

        return response()->json($coupons);
    }

    /**
     * Validate a coupon code
     */
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'cart_total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon = Coupon::where('code', strtoupper($request->code))
            ->where('status', 'active')
            ->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid coupon code'
            ], 404);
        }

        // Check if coupon is expired
        if ($coupon->end_date && $coupon->end_date < now()) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon has expired'
            ]);
        }

        // Check if coupon hasn't started yet
        if ($coupon->start_date > now()) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon is not yet active'
            ]);
        }

        // Check usage limit
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon has reached its usage limit'
            ]);
        }

        // Check minimum order amount
        if ($coupon->minimum_order_amount && $request->cart_total < $coupon->minimum_order_amount) {
            return response()->json([
                'valid' => false,
                'message' => "Minimum order amount of {$coupon->minimum_order_amount} required"
            ]);
        }

        // Calculate discount
        $discount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discount = ($request->cart_total * $coupon->discount_value) / 100;
            if ($coupon->max_discount_amount) {
                $discount = min($discount, $coupon->max_discount_amount);
            }
        } else {
            $discount = $coupon->discount_value;
        }

        return response()->json([
            'valid' => true,
            'message' => 'Coupon is valid',
            'coupon' => $coupon,
            'discount_amount' => round($discount, 2)
        ]);
    }

    /**
     * Store a new coupon
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon = Coupon::create([
            'seller_id' => $request->user()->id,
            'code' => strtoupper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'minimum_order_amount' => $request->minimum_order_amount,
            'max_discount_amount' => $request->max_discount_amount,
            'usage_limit' => $request->usage_limit,
            'usage_count' => 0,
            'description' => $request->description,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => $coupon
        ], 201);
    }

    /**
     * Update a coupon
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::where('seller_id', $request->user()->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $id,
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,expired',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);

        return response()->json([
            'message' => 'Coupon updated successfully',
            'data' => $coupon
        ]);
    }

    /**
     * Delete a coupon
     */
    public function destroy(Request $request, $id)
    {
        $coupon = Coupon::where('seller_id', $request->user()->id)
            ->findOrFail($id);

        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully'
        ]);
    }
}

