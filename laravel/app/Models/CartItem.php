<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'user_id', 'product_id', 'quantity', 'unit_price'
    ];

    // If your cart_items table has NO created_at/updated_at columns, uncomment this:
    // public $timestamps = false;

    protected $appends = ['line_total'];

    public function getLineTotalAttribute(): string
    {
        return number_format($this->quantity * (float) $this->unit_price, 2, '.', '');
    }

    // Relationships (optional)
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }
}
