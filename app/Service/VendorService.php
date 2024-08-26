<?php

namespace App\Service;

use App\Models\Vendor;

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

    public function deleteVendor(int|string $vendor_id)
    {
        $vendor = $this->getVendorById($vendor_id);
        return $vendor->delete();
    }
}
