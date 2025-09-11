<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'photo_path', 'address', 'facebook_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


     public function getPhotoUrlAttribute()
        {
            return $this->photo_path
                ? Storage::disk('public')->url($this->photo_path)
                : null;
        }

    // Optional: convenient accessor for public URL
    protected $appends = ['photo_url'];


}
