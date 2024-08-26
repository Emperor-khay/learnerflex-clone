<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referred_user_id',
        'referral_code',
        'status',
    ];

    /**
     * Get the user who made the referral.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who was referred.
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
