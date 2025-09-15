<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Disable created_at / updated_at auto columns
    public $timestamps = false;

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
