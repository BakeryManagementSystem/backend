<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(Request $request, $productId)
    {
        $reviews = ProductReview::where('product_id', $productId)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($reviews);
    }

    /**
     * Store a new review
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();

        // Check if user has already reviewed this product for this order
        $existingReview = ProductReview::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->where('order_id', $request->order_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product for this order.'
            ], 422);
        }

        // Check if the order is delivered (if order_id is provided)
        $isVerifiedPurchase = false;
        if ($request->order_id) {
            $order = Order::where('id', $request->order_id)
                ->where('buyer_id', $userId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or does not belong to you.'
                ], 404);
            }

            if ($order->status !== 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only review products from delivered orders.'
                ], 422);
            }

            // Check if product is in the order
            $orderItem = DB::table('order_items')
                ->where('order_id', $order->id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$orderItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product is not in the specified order.'
                ], 422);
            }

            $isVerifiedPurchase = true;
        }

        $review = ProductReview::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'review' => $request->review,
            'is_verified_purchase' => $isVerifiedPurchase,
        ]);

        $review->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully!',
            'data' => $review
        ], 201);
    }

    /**
     * Update a review
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $review = ProductReview::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you do not have permission to update it.'
            ], 404);
        }

        $review->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        $review->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully!',
            'data' => $review
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy($id)
    {
        $review = ProductReview::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found or you do not have permission to delete it.'
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully!'
        ]);
    }

    /**
     * Check if user can review a product from an order
     */
    public function canReview(Request $request, $orderId, $productId)
    {
        $userId = Auth::id();

        // Check if order exists and belongs to user
        $order = Order::where('id', $orderId)
            ->where('buyer_id', $userId)
            ->first();

        if (!$order || $order->status !== 'delivered') {
            return response()->json([
                'can_review' => false,
                'reason' => 'Order must be delivered to leave a review.'
            ]);
        }

        // Check if product is in order
        $orderItem = DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'can_review' => false,
                'reason' => 'Product not found in this order.'
            ]);
        }

        // Check if already reviewed
        $existingReview = ProductReview::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('order_id', $orderId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'can_review' => false,
                'reason' => 'You have already reviewed this product.',
                'review' => $existingReview
            ]);
        }

        return response()->json([
            'can_review' => true
        ]);
    }

    /**
     * Get product statistics including reviews
     */
    public function getProductStats($productId)
    {
        $product = Product::findOrFail($productId);

        $reviews = ProductReview::where('product_id', $productId)->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        // Rating distribution
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $reviews->where('rating', $i)->count();
            $ratingDistribution[$i] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0
            ];
        }

        return response()->json([
            'product_id' => $productId,
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
        ]);
    }
}

