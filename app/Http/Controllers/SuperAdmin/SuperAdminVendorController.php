<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;

class SuperAdminVendorController extends Controller
{
    // Get all vendors
    public function index()
    {
        $vendors = Vendor::with(['user:id,email,aff_id,phone'])->paginate(10);

        return response()->json($vendors);
    }

    // Get a single vendor
    public function show($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        return response()->json($vendor);
    }

    // Update a vendor
    public function update(Request $request, $id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        $vendor->update($request->all());

        return response()->json(['message' => 'Vendor updated successfully', 'vendor' => $vendor]);
    }
}
