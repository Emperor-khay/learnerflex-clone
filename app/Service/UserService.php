<?php

namespace App\Service;

use App\Enums\VendorStatusEnum;
use App\Models\User;
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

    public function getUserVendor(User $user)
    {
        return $user->vendor;
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
