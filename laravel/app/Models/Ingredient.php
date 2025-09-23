<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['name','unit','current_unit_price'];

    // Relationship to ingredient batch items
    public function batchItems()
    {
        return $this->hasMany(IngredientBatchItem::class);
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
