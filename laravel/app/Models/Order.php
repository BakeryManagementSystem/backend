<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['buyer_id', 'buyer_address', 'buyer_phone', 'status', 'total_amount'];

    // If you removed created_at/updated_at from the table, uncomment:
    // public $timestamps = false;

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
}
