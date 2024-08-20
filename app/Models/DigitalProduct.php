<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'seller_name',
        'price',
        'commission',
        'contact_email',
        'affiliate_link',
        'vsl_pa_link',
        'access_link',
        'sale_challenge_link',
        'promotional_material',
        'is_partnership',
        'x_link',
        'ig_link',
        'yt_link',
        'fb_link',
        'tt_link',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_partnership' => 'boolean',
        ];
    }
}
