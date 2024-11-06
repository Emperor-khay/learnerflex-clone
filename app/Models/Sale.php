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
        'transaction_id',     // Foreign key referencing the transaction
        'product_id',         // Foreign key referencing the product
        'vendor_id',          // Foreign key referencing the vendor (user who owns the product)
        'affiliate_id',       // Foreign key referencing the affiliate (if any)
        'amount',             // Total sale amount
        'status',             // Status of the sale (e.g., pending, completed)
        'commission',         // Commission earned by the affiliate
        'currency',           // Currency used in the sale
        'email',              // Buyer's email
        'org_vendor',         // Vendor's share in the sale
        'org_aff',            // Affiliate's share in the sale (if any)
        'org_company',        // Company's share in the sale (e.g., admin fee)
    ];

   // Relationship to the user who is the vendor of the product
   public function vendor()
   {
       return $this->belongsTo(User::class, 'vendor_id');
   }

    /**
     * A sale may involve an affiliate earning commission.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    /**
     * A sale is associated with a product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * A sale is associated with a transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
