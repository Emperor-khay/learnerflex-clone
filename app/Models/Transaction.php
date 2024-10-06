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
        'email', //email of user making payment
        'transaction_id',
        'amount',
        'currency',
        'status',
        'is_onboard',
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
            'is_onboard' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
