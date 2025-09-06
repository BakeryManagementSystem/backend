<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopProfile extends Model
{
    protected $fillable = [
        'owner_id', 'shop_name', 'address', 'phone', 'logo_path', 'facebook_url'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    protected $appends = ['logo_url'];
    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? url(\Illuminate\Support\Facades\Storage::url($this->logo_path)) : null;
    }
}
