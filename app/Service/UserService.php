<?php

namespace App\Service;

use App\Enums\VendorStatusEnum;
use App\Models\User;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Affiliate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function newUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create($data);
        });
    }

    public function getUserById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function getUserByEmail(string $email): User
    {
        return User::where('email', $email)->first();
    }

    public function getUsersByRole(array $role)
    {
        return User::whereJsonContains('role', $role)->get();
    }

    public function getUsersByCountry(string $country)
    {
        return User::where('country', $country)->get();
    }

    public function getUserByPhone(string $phone): User
    {
        return User::where('phone', $phone)->first();
    }

    public function getAllUsers()
    {
        return User::all();
    }

    public function createTransactionForUser(User $user, array $transactionData)
    {
        return $user->transactions()->create($transactionData);
    }

    public function getTransactionsForUser(User $user)
    {
        return $user->transactions;
    }

    public function createVendorForUser(User $user, array $vendorData)
    {
        return DB::transaction(function () use ($user, $vendorData) {
            return $user->vendor()->create($vendorData);
        });
    }

    public function createAccountForUser(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            if($user->account) {
                return $user->account()->update($data);
            }
            return $user->account()->create($data);
        });
    }


    public function getUserVendor(User $user)
    {
        return $user->vendor;
    }

    public function getUserWithdrawals(User $user)
    {
        return $user->withdrawals;
    }

    public function totalAffSales(User $user)
    {
        // user who is requesting for the afffiliate sales
        // if vendor has affiliates then return sales
        $affs = $user->affiliates;
        if (count($affs) < 1) {
            return [
                'amount' => 0,
                'sales' => 0
            ];
        }
        return $affSales = Sale::where('user_id', $user->id)->get();
    }

    public function todaysAffSales(User $user)
    {
        // Check if the user has affiliates
        $affs = $user->affiliates;
        if (count($affs) < 1) {
            return [
                'amount' => 0,
                'sales' => 0
            ];
        }

        // Get today's date
        $today = Carbon::today();

        // Query sales made today
        $todaysSales = Sale::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();

        // Calculate the total amount and number of sales
        $totalAmount = $todaysSales->sum('amount');
        $totalSales = $todaysSales->count();

        return [
            'amount' => $totalAmount,
            'sales' => $totalSales
        ];
    }


    public function updateUserCurrency(User $user, string $currency)
    {
        return $user->update([
            'currency' => $currency
        ]);
    }

    public function updateUserImage(User $user, string $newPath)
    {
        return DB::transaction(function () use ($user, $newPath) {
            $user->image = $newPath;
            $user->save();
            return $user->refresh();
        });
    }

    public function updateUserDetails(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update($data);
            return $user->refresh();
        });
    }

    public function updateUserVendorApplication(User $user, string $data)
    {
        if($user->vendorStatus) {
            throw new \Exception('vendor request already exists', 422);
        }
        return DB::transaction(function () use ($user, $data) {
            $user->vendorStatus()->create(['sale_url' => $data]);
            $user->update(['vendor_status' => VendorStatusEnum::PENDING->value]);
            return $user->refresh();
        });
    }

    public function updateUserVendorStatus(User $user)
    {
        if($user->is_vendor) {
            throw new \Exception('User is already a vendor!', 422);
        }
        return DB::transaction(function () use ($user) {
            $user->update([
                'is_vendor' => true,
                'vendor_status' => VendorStatusEnum::UP->value,
            ]);
            return $user->refresh();
        });
    }
}
