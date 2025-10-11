<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ingredient_id',
        'quantity_required',
        'unit',
        'cost_per_unit',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Calculate total cost for this ingredient in the product
     */
    public function getTotalCostAttribute()
    {
        return $this->quantity_required * $this->cost_per_unit;
    }
}

