<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'affiliate_id',
        'amount',
        'transaction_id',
    ];

    /**
     * Get the product that was sold.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who made the purchase.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the affiliate who promoted the sale.
     */
    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }
}
