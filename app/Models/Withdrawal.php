<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdrawal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'bank_account',
        'bank_name',
        'email',
        'old_balance',
        'bankcode',
        'status',
        'type',
    ];

    protected $casts = [
        'status' => WithdrawalStatus::class,
    ];

    /**
     * Get the user associated with the withdrawal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
