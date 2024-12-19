<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tx_ref',
        'user_id', // id of user who own product
        'affiliate_id', //user id id of affilate who reffered user
        'product_id',
        'vendor_id',
        'email',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'is_onboarded',
        'description',
        'user_id',
        'org_company',
        'org_vendor',
        'org_aff',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_onboarded' => 'boolean',
        ];
    }

     // Relationship to the user who made the purchase
     public function buyer()
     {
         return $this->belongsTo(User::class, 'user_id');
     }
 
     // Relationship to the user who is the vendor of the product
     public function vendor()
     {
         return $this->belongsTo(User::class, 'vendor_id');
     }
 
     // Relationship to the affiliate user involved in the transaction
     public function affiliate()
     {
         return $this->belongsTo(User::class, 'affiliate_id');
     }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'transaction_id');
    }
}
