<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'settings',
        'average_rating',
        'total_reviews',
        'total_products',
        'total_sales',
        'verified'
    ];

    protected $casts = [
        'theme' => 'array',
        'policies' => 'array',
        'social' => 'array',
        'settings' => 'array',
        'average_rating' => 'float',
        'total_reviews' => 'integer',
        'total_products' => 'integer',
        'total_sales' => 'integer',
        'verified' => 'boolean'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? url(\Storage::url($this->logo_path)) : null;
    }

    public function getBannerUrlAttribute()
    {
        return $this->banner_path ? url(\Storage::url($this->banner_path)) : null;
    }
}
