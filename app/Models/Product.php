<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'vendor_id',
        'name',
        'description',
        'image',
        'price',
        'old_price',
        'type',
        'commission',
        'contact_email',
        'access_link',
        'vsl_pa_link',
        'access_link',
        'sale_page_link',
        'sale_challenge_link',
        'promotional_material',
        'is_partnership',
        'is_affiliated',
        'x_link',
        'ig_link',
        'yt_link',
        'fb_link',
        'tt_link',
        'status',
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
            'is_affiliated' => 'boolean',
        ];
    }

    /**
     * Get the vendor that owns the product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
