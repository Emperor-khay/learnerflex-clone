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
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    /**
     * Get the affiliates of the user.
     */
    public function affiliates(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    /**
     * Get the vendor request data of the user.
     */
    public function vendorStatus(): HasOne
    {
        return $this->hasOne(VendorStatus::class);
    }
}
