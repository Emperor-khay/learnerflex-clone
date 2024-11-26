<?php

use Illuminate\Support\Facades\Storage;

function generateTemporaryUrlForProductFile($product)
{
    if ($product->file) {
        return Storage::disk('private')->temporaryUrl(
            $product->file,
            now()->addMinutes(30) // Set the URL expiration time
        );
    }

    return null;
}