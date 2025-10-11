<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'current_unit_price',
        'current_stock',
        'minimum_stock_level',
        'maximum_stock_level',
        'supplier_name',
        'supplier_contact',
        'storage_location',
        'expiry_date',
        'category',
        'description',
        'cost_per_unit',
        'status'
    ];

    protected $casts = [
        'current_unit_price' => 'decimal:2',
        'current_stock' => 'decimal:3',
        'minimum_stock_level' => 'decimal:3',
        'maximum_stock_level' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'status' => 'boolean',
        'expiry_date' => 'date',
    ];

    // Relationship to ingredient batch items
    public function batchItems()
    {
        return $this->hasMany(IngredientBatchItem::class);
    }

    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('quantity_required', 'unit', 'cost_per_unit')
            ->withTimestamps();
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // Check if ingredient can be safely deleted
    public function canBeDeleted()
    {
        return $this->batchItems()->count() === 0;
    }

    // Get the count of batch items using this ingredient
    public function getBatchItemsCount()
    {
        return $this->batchItems()->count();
    }
}
