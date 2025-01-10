<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Helper
{
    public static function generateDownloadLink($productId)
    {
        // Fetch the product by ID
        $product = Product::find($productId);

        if (!$product) {
            throw new \Exception("Product not found"); // Throw an exception if the product doesn't exist
        }

        $expiryTime = now()->addDays(7); // Token valid for 7 days
        $data = [
            'product_id' => $product->id,
            'expires_at' => $expiryTime->timestamp, // Save as a UNIX timestamp
        ];

        $encryptedToken = Crypt::encrypt($data);

        return route('product.download', ['token' => $encryptedToken]);
    }

    // public static function canSellProduct(string $aff_id, int $product_id): bool
    // {
    //     // Find the affiliate user
    //     $affiliate = User::where('aff_id', $aff_id)->first();

    //     if (!$affiliate) {
    //         return false; // Affiliate does not exist
    //     }

    //     // Check if the affiliate has market access
    //     if ($affiliate->market_access) {
    //         return true; // Full access
    //     }

    //     // Retrieve the product and vendor
    //     $product = Product::find($product_id);
    //     if (!$product || !$product->user_id) {
    //         return false; // Product does not exist or is not linked to a vendor
    //     }

    //     $vendorId = $product->user_id;

    //     // Check if the affiliate has a successful purchase from this vendor
    //     $hasPurchased = Transaction::where('email', $affiliate->email)
    //         ->where('status', 'success')
    //         ->where('vendor_id', $vendorId)
    //         ->exists();

    //     if ($hasPurchased) {
    //         return true; // Affiliate has purchased from this vendor
    //     }

    //     // Check if the affiliate is onboarded with this vendor
    //     $isOnboarded = Transaction::where('email', $affiliate->email)
    //         ->where('is_onboarded', true)
    //         ->where('vendor_id', $vendorId)
    //         ->exists();

    //     if ($isOnboarded) {
    //         return true; // Affiliate is onboarded with this vendor
    //     }

    //     return false; // Affiliate does not meet any criteria
    // }

    public static function canSellProduct(string $aff_id, int $product_id): bool
    {
        // Find the affiliate user
        $affiliate = User::where('aff_id', $aff_id)->first();

        if (!$affiliate) {
            return false; // Affiliate does not exist
        }

        // Check if the affiliate has market access
        if ($affiliate->market_access) {
            return true; // Full access
        }

        // Retrieve the product and vendor
        $product = Product::find($product_id);
        if (!$product || !$product->user_id) {
            return false; // Product does not exist or is not linked to a vendor
        }

        $vendorId = $product->user_id;

        // Check if the affiliate is the owner of the product
        if ($affiliate->id === $vendorId) {
            return true; // Affiliate is the owner of the product
        }

        // Check if the affiliate has a successful purchase from this vendor
        $hasPurchased = Transaction::where('email', $affiliate->email)
            ->where('status', 'success')
            ->where('vendor_id', $vendorId)
            ->exists();

        if ($hasPurchased) {
            return true; // Affiliate has purchased from this vendor
        }

        // Check if the affiliate is onboarded with this vendor
        $isOnboarded = Transaction::where('email', $affiliate->email)
            ->where('is_onboarded', true)
            ->where('vendor_id', $vendorId)
            ->exists();

        if ($isOnboarded) {
            return true; // Affiliate is onboarded with this vendor
        }

        return false; // Affiliate does not meet any criteria
    }
}
