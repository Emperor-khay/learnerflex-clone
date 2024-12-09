<?php

namespace App\Helpers;

use App\Models\Product;
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
}
