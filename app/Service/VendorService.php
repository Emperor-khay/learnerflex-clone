<?php

namespace App\Service;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class VendorService
{
    public function newVendor(array $vendorData): Vendor
    {
        return Vendor::create($vendorData);
    }

    public function getVendorById(int|string $vendor_id): Vendor
    {
        return Vendor::findOrFail($vendor_id);
    }

    public function newVendorProduct(Vendor $vendor, $productData): Product
    {
        return DB::transaction(function () use ($vendor, $productData) {
            return $vendor->products()->create($productData);
        });
    }

    public function deleteVendor(int|string $vendor_id)
    {
        $vendor = $this->getVendorById($vendor_id);
        return $vendor->delete();
    }
}
