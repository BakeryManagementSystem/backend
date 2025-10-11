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

    // Override attribute casting to handle malformed JSON gracefully
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // Handle JSON fields that might be malformed
        if (in_array($key, ['theme', 'policies', 'social', 'settings']) && is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            } catch (\Exception $e) {
                \Log::warning("Malformed JSON in {$key} for shop profile {$this->id}");
                return [];
            }
        }

        return $value;
    }

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
