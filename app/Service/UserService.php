<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function newUser(array $data): User
    {
        return User::create($data);
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
            return $user->vendors()->create($vendorData);
        });
    }

    public function getUserVendors(User $user)
    {
        return $user->vendors;
    }

    public function updateUserCurrency(User $user, string $currency)
    {
        return $user->update([
            'currency' => $currency
        ]);
    }
}
