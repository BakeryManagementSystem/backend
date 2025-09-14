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
        'owner_id', 'name', 'description', 'price', 'category', 'image_path'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        // returns absolute URL like http://localhost:8000/storage/products/xyz.jpg
        return url(Storage::url($this->image_path));
    }

        public $timestamps = false;
    // Optional:
    // public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
}
