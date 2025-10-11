<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /**
     * Get all expenses for the authenticated owner
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Expense::where('owner_id', $user->id)
            ->orderBy('expense_date', 'desc');

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $expenses = $query->paginate($perPage);

        return response()->json($expenses);
    }

    /**
     * Store a new expense
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense = Expense::create([
            'owner_id' => $request->user()->id,
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'description' => $request->description,
            'payment_method' => $request->payment_method,
            'vendor' => $request->vendor,
            'receipt_number' => $request->receipt_number,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Expense created successfully',
            'data' => $expense
        ], 201);
    }

    /**
     * Get a specific expense
     */
    public function show(Request $request, $id)
    {
        $expense = Expense::where('owner_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($expense);
    }

    /**
     * Update an expense
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::where('owner_id', $request->user()->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense->update($request->all());

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => $expense
        ]);
    }

    /**
     * Delete an expense
     */
    public function destroy(Request $request, $id)
    {
        $expense = Expense::where('owner_id', $request->user()->id)
            ->findOrFail($id);

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully'
        ]);
    }

    /**
     * Get expense statistics
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $expenses = Expense::where('owner_id', $user->id)
            ->whereBetween('expense_date', [$startDate, $endDate]);

        $totalExpenses = $expenses->sum('amount');
        $expensesByCategory = Expense::where('owner_id', $user->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        return response()->json([
            'total_expenses' => $totalExpenses,
            'by_category' => $expensesByCategory,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]);
    }
}

