<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class ShopProfile extends Model
{
    protected $fillable = [
        'owner_id',
        'shop_name',
        'description',
        'address',
        'phone',
        'logo_path',
        'banner_path',
        'facebook_url',
        'theme',
        'policies',
        'social',
        'settings'
    ];

    protected $casts = [
        'theme' => 'array',
        'policies' => 'array',
        'social' => 'array',
        'settings' => 'array'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    protected $appends = ['logo_url', 'banner_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? url(\Storage::url($this->logo_path)) : null;
    }

    public function getBannerUrlAttribute()
    {
        return $this->banner_path ? url(\Storage::url($this->banner_path)) : null;
    }
}
