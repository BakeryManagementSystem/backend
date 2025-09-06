<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IngredientBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'category', 'period_start', 'period_end', 'notes'
    ];

    public function items()
    {
        return $this->hasMany(IngredientBatchItem::class, 'batch_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
