<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    /**
     * Get all inventory transactions
     */
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['ingredient', 'user'])
            ->orderBy('transaction_date', 'desc');

        // Filter by ingredient
        if ($request->has('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        $perPage = $request->input('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Record a new inventory transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ingredient_id' => 'required|exists:ingredients,id',
            'transaction_type' => 'required|in:purchase,usage,adjustment,waste,return',
            'quantity' => 'required|numeric',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $ingredient = Ingredient::findOrFail($request->ingredient_id);

            // Calculate total cost
            $unitPrice = $request->unit_price ?? $ingredient->current_unit_price;
            $totalCost = abs($request->quantity) * $unitPrice;

            // Create transaction
            $transaction = InventoryTransaction::create([
                'ingredient_id' => $request->ingredient_id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'notes' => $request->notes,
                'user_id' => $request->user()->id,
                'transaction_date' => now(),
            ]);

            // Update ingredient stock
            $this->updateIngredientStock($ingredient, $request->transaction_type, $request->quantity);

            DB::commit();

            return response()->json([
                'message' => 'Inventory transaction recorded successfully',
                'data' => $transaction->load(['ingredient', 'user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to record transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ingredient stock based on transaction type
     */
    private function updateIngredientStock($ingredient, $transactionType, $quantity)
    {
        switch ($transactionType) {
            case 'purchase':
            case 'return':
                // Increase stock
                $ingredient->current_stock += abs($quantity);
                break;

            case 'usage':
            case 'waste':
                // Decrease stock
                $ingredient->current_stock -= abs($quantity);
                break;

            case 'adjustment':
                // Can be positive or negative
                $ingredient->current_stock += $quantity;
                break;
        }

        $ingredient->save();
    }

    /**
     * Get inventory transaction statistics
     */
    public function getStats(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $stats = [
            'total_purchases' => InventoryTransaction::where('transaction_type', 'purchase')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('total_cost'),

            'total_usage' => InventoryTransaction::where('transaction_type', 'usage')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('total_cost'),

            'total_waste' => InventoryTransaction::where('transaction_type', 'waste')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('total_cost'),

            'transaction_count' => InventoryTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->count(),

            'by_type' => InventoryTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('transaction_type, COUNT(*) as count, SUM(total_cost) as total')
                ->groupBy('transaction_type')
                ->get(),
        ];

        return response()->json($stats);
    }
}

