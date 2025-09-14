<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\IngredientBatchItem;
use Illuminate\Support\Facades\DB;

class IngredientBatchController extends Controller
{
    // GET /api/owner/ingredient-batches?category=Cakes&from=2025-09-01&to=2025-09-30
    public function index(Request $request)
    {
        $ownerId = $request->user()->id;
        $category = $request->query('category');
        $from = $request->query('from');
        $to   = $request->query('to');

        $q = IngredientBatch::with('items.ingredient')
            ->where('owner_id', $ownerId)
            ->orderBy('id', 'desc');

        if ($category) $q->where('category', $category);
        if ($from) $q->where(function($qq) use ($from) {
            $qq->whereNull('period_start')->orWhere('period_start', '>=', $from);
        });
        if ($to) $q->where(function($qq) use ($to) {
            $qq->whereNull('period_end')->orWhere('period_end', '<=', $to);
        });

        return response()->json(['data' => $q->get()]);
    }

    // POST /api/owner/ingredient-batches  (create a batch with items)
    // Body JSON example:
    // {
    //   "category": "Cakes",
    //   "period_start": "2025-09-01",
    //   "period_end": "2025-09-07",
    //   "notes": "First week production",
    //   "items": [
    //     { "ingredient_id": 1, "quantity_used": 20, "unit_price_snapshot": 80 },
    //     { "ingredient_id": 2, "quantity_used": 5,  "unit_price_snapshot": 200 },
    //     { "ingredient_id": 3, "quantity_used": 120,"unit_price_snapshot": 12 }
    //   ]
    // }
    public function store(Request $request)
    {
        $ownerId = $request->user()->id;
        $data = $request->validate([
            'category' => ['required','string','max:120'],
            'period_start' => ['nullable','date'],
            'period_end'   => ['nullable','date'],
            'notes' => ['nullable','string','max:255'],
            'items' => ['required','array','min:1'],
            'items.*.ingredient_id' => ['required','integer','exists:ingredients,id'],
            'items.*.quantity_used' => ['required','numeric','min:0.0001'],
            'items.*.unit_price_snapshot' => ['required','numeric','min:0'],
        ]);

        $batch = DB::transaction(function() use ($data, $ownerId) {
            $batch = IngredientBatch::create([
                'owner_id' => $ownerId,
                'category' => $data['category'],
                'period_start' => $data['period_start'] ?? null,
                'period_end'   => $data['period_end'] ?? null,
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $it) {
                $lineCost = (float)$it['quantity_used'] * (float)$it['unit_price_snapshot'];
                IngredientBatchItem::create([
                    'batch_id'            => $batch->id,
                    'ingredient_id'       => $it['ingredient_id'],
                    'quantity_used'       => $it['quantity_used'],
                    'unit_price_snapshot' => $it['unit_price_snapshot'],
                    'line_cost'           => $lineCost,
                ]);
            }

            return $batch->load('items.ingredient');
        });

        return response()->json(['message' => 'Batch saved', 'batch' => $batch], 201);
    }

    // DELETE /api/owner/ingredient-batches/{batch}
    public function destroy(Request $request, IngredientBatch $batch)
    {
        $ownerId = $request->user()->id;
        if ($batch->owner_id !== $ownerId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $batch->delete();
        return response()->json(['message' => 'Batch deleted']);
    }
}
