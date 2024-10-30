<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Vendor extends Model
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
        'photo',
        'description','x_link', 'ig_link', 'yt_link', 'fb_link', 'tt_link', 'display',
    ];

    protected static function boot()
    {
        parent::boot();

        // Register a model event listener for the deleting event
        static::deleting(function ($vendor) {
            if ($vendor->photo) {
                // Delete the photo from storage
                Storage::disk('public')->delete($vendor->photo);
            }
        });
    }

    
    // Relationship with Product model
    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
}
