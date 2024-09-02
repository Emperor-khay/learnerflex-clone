<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->aff_id = Str::uuid7();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'country',
        'image',
        'has_paid_onboard',
        'is_vendor',
        'vendor_status',
        'otp',
        'market_access',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_paid_onboard' => 'boolean',
            'is_vendor' => 'boolean',
            'market_access' => 'boolean',
        ];
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the vendor associated with the user.
     */
    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Get the reviews of the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the withdrawals of the user.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Get the account model of the user.
     * This is the User's Bank account.
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Get the affiliates of the user.
     * These are the 
     */
    public function affiliates(): HasMany
    {
        return $this->hasMany(Affiliate::class);
    }

    // user is considered an affiliate once one purchases a 
    // product from a vendor, they are entitled to all the products of the vendor. As for the marketplace they can only see the products that they are affiliated with their vendor. The rest needs to be paid before unlocking them to be able to promote it. You can use ur own link to make purchase for urself. Upon purchasing a product, the user has to fill their details in, to create an account for them if it doesnt exist and send the account details to their email.
    // users who sign up and pay onboard fee have access to all products in the marketplace, whereas the affiliates dont 

    /**
     * Get the vendor request data of the user.
     */
    public function vendorStatus(): HasOne
    {
        return $this->hasOne(VendorStatus::class);
    }
}
