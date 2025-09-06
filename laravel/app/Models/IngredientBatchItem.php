<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id', 'ingredient_id', 'quantity_used', 'unit_price_snapshot', 'line_cost'
    ];

    public function batch()
    {
        return $this->belongsTo(IngredientBatch::class, 'batch_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
