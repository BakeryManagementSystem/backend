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
        $ingredient->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
