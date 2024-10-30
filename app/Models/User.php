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
        // Generate a unique 8-character aff_id
        do {
            $user->aff_id = Str::random(8);
            $exists = User::where('aff_id', $user->aff_id)->exists();
        } while ($exists); // Ensure it's unique before setting
    });
}

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'aff_id',
        'email',
        'refferal_id',
        'phone',
        'password',
        'country',
        'role',
        'image',
        'is_vendor',
        'vendor_status',
        'otp',
        'market_access',
        'bank_name',
        'bank_account'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    /**
     * Check if the user is an affiliate.
     *
     * @return bool
     */
    public function isAffiliate(): bool
    {
        return $this->role === 'affiliate';
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }


    /**
     * A vendor (user) can have many products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    /**
     * A vendor or affiliate can have many sales.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'user_id');
    }


    /**
     * A user (vendor or affiliate) can have transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

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
