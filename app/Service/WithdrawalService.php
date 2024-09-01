<?php

namespace App\Service;

use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    /**
     * Get all withdrawals
     */
    public function getWithdrawals()
    {
        return Withdrawal::all();
    }

    /**
     * Get Paginated withdrawals
     * size of 10
     */
    public function getWithdrawalsPaginated(int $page = 1)
    {
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        return Withdrawal::orderBy('created_at', 'desc')
                ->skip($offset)
                    ->take($pageSize)
                        ->get();
    }

    /**
     * Get the latest 10 withdrawals
     */
    public function getLatestWithdrawals()
    {
        return Withdrawal::orderBy('created_at', 'desc')
                ->take(10)
                    ->get();
    }

    /**
     * Create a new withdrawal by model
     */
    public function newWithdrawal(array $data): Withdrawal
    {
        return DB::transaction(function () use ($data) {
           return Withdrawal::create($data);
        });
    }

    /**
     * Create a new withdrawal using User relation.
     */
    public function userMakeWithdrawal(User $user, array $data): Withdrawal
    {
        return DB::transaction( function () use ($user, $data) {
            return $user->withdrawals()->create($data);
        });
    }

    /**
     * Get a Withdrawal
     */
    public function getOneWithdrawal(int $id): Withdrawal
    {
        return Withdrawal::findOrFail($id);
    }

    /**
     * Get withdrawals by bank account
     */
    public function getWithdrawalsByAcctNumber(string $bn)
    {
        return Withdrawal::where('bank_account', $bn)->get();
    }

    /**
     * Get withdrawals by User id
     */
    public function getWithdrawalsByUser(int|string $user_id)
    {
        return Withdrawal::where('user_id', $user_id)->get();
    }

    /**
     * Get Withdrawals by status
     */
    public function getWithdrawalsByStatus(string $status)
    {
        return Withdrawal::where('status', $status)->get();
    }
}
