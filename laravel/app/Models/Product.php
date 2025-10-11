<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'price',
        'discount_price',
        'category',
        'category_id',
        'stock_quantity',
        'sku',
        'weight',
        'dimensions',
        'ingredients',
        'allergens',
        'status',
        'rating',
        'rating_count',
        'is_featured',
        'meta_title',
        'meta_description',
        'image_path',
        'images'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'weight' => 'decimal:2',
        'stock_quantity' => 'integer',
        'category_id' => 'integer',
        'rating_count' => 'integer',
        'is_featured' => 'boolean',
        'ingredients' => 'array',
        'allergens' => 'array',
        'images' => 'array'
    ];

    protected $appends = ['image_url', 'image_urls'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        // If it's already a URL, ensure it's HTTPS
        if (str_starts_with($this->image_path, 'http')) {
            return str_replace('http://', 'https://', $this->image_path);
        }

        // Otherwise prepend secure storage URL
        return secure_url(Storage::url($this->image_path));
    }

    public function getImageUrlsAttribute(): array
    {
        if (!$this->images || !is_array($this->images)) {
            return [];
        }

        return array_map(function($path) {
            // If it's already a URL, ensure it's HTTPS
            if (str_starts_with($path, 'http')) {
                return str_replace('http://', 'https://', $path);
            }

            // Otherwise prepend secure storage URL
            return secure_url(Storage::url($path));
        }, $this->images);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function productIngredients()
    {
        return $this->hasMany(ProductIngredient::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
}
