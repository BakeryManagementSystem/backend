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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // If you're already doing Hash::make() in the controller, you can remove the 'password' => 'hashed' cast
        // OR keep it and send plaintext here (not both). With your current controller, remove it:
        // 'password' => 'hashed',
    ];
}
