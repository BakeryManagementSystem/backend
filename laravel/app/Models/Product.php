<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    // Dynamically check if timestamp columns exist
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Only enable timestamps if the columns exist in the database
        $this->timestamps = Schema::hasColumns('products', ['created_at', 'updated_at']);
    }

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
        'is_featured',
        'meta_title',
        'meta_description',
        'image_path',
        'images'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'stock_quantity' => 'integer',
        'category_id' => 'integer',
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
        return url(Storage::url($this->image_path));
    }

    public function getImageUrlsAttribute(): array
    {
        if (!$this->images || !is_array($this->images)) {
            return [];
        }
        return array_map(fn($path) => url(Storage::url($path)), $this->images);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }
}
