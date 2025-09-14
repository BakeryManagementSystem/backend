<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    // We’re using a single timestamp column sold_at, so disable default timestamps:
    public $timestamps = false;

    protected $fillable = [
        'owner_id','buyer_id','order_id','product_id','quantity','unit_price','line_total','sold_at'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
