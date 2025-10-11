<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'transaction_type',
        'quantity',
        'unit_price',
        'total_cost',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
        'transaction_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    // Transaction types
    const TYPE_PURCHASE = 'purchase';
    const TYPE_USAGE = 'usage';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_WASTE = 'waste';
    const TYPE_RETURN = 'return';

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (polymorphic relation)
     */
    public function reference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Scope to filter by transaction type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}

