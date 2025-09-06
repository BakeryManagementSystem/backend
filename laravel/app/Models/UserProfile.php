<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'photo_path', 'address', 'facebook_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Optional: convenient accessor for public URL
    protected $appends = ['photo_url'];
    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? url(\Illuminate\Support\Facades\Storage::url($this->photo_path)) : null;
    }
}
