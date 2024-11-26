<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'token', 'expires_at'];
    public $timestamps = true;

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
