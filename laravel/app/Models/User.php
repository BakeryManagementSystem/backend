<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Dynamically check if timestamp columns exist
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Only enable timestamps if the columns exist in the database
        $this->timestamps = Schema::hasColumns('users', ['created_at', 'updated_at']);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type', // add this since you use it
        'phone',
        'date_of_birth',
        'avatar'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date'
    ];

    public function profile() { return $this->hasOne(UserProfile::class); }
    public function shopProfile() { return $this->hasOne(ShopProfile::class, 'owner_id'); }

    // Relationships
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'owner_id');
    }
}
