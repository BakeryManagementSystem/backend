<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;

class IngredientController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Ingredient::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'unit' => ['required','string','max:30'],
            'current_unit_price' => ['required','numeric','min:0'],
        ]);
        $ing = Ingredient::create($data);
        return response()->json(['message'=>'Ingredient created','ingredient'=>$ing], 201);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'unit' => ['nullable','string','max:30'],
            'current_unit_price' => ['nullable','numeric','min:0'],
        ]);
        if (isset($data['unit'])) $ingredient->unit = $data['unit'];
        if (isset($data['current_unit_price'])) $ingredient->current_unit_price = $data['current_unit_price'];
        $ingredient->save();
        return response()->json(['message'=>'Updated','ingredient'=>$ingredient]);
    }

    public function destroy(Ingredient $ingredient)
    {

        if (!$ingredient->canBeDeleted()) {
            $batchItemsCount = $ingredient->getBatchItemsCount();
            return response()->json([
                'message' => "Cannot delete ingredient '{$ingredient->name}' because it is being used in {$batchItemsCount} batch item(s). Please remove the ingredient from all batches before deleting.",
                'error' => 'CONSTRAINT_VIOLATION',
                'details' => [
                    'ingredient_name' => $ingredient->name,
                    'batch_items_count' => $batchItemsCount
                ]
            ], 422);
        }

        try {
            $ingredient->delete();
            return response()->json(['message' => 'Ingredient deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete ingredient due to a database constraint.',
                'error' => 'DELETE_FAILED'
            ], 500);
        }
    }
}
