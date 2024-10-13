<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;

class SuperAdminProductController extends Controller
{
    public function index(Request $request)
{
    // Get the 'per_page' query parameter or default to 15
    $perPage = $request->get('per_page', 15); // Default is 15 products per page
    
    // Fetch the products with pagination
    $products = Product::paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $products->items(), // The products data
        'pagination' => [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
            'per_page' => $products->perPage(),
        ]
    ]);
}


    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    
    public function store(Request $request)
    {

        $vendor = Vendor::where('user_id', $request->user_id)->first();
        $firstUser = User::first();

        if (!$firstUser) {
            return response()->json(['message' => 'No users found in the database'], 400);
        }

        if(!$vendor){
            return response()->json([
                'message' => 'vendor not found'
            ]);
        }

        $vendor_id = $vendor->id;
        
        $request->validate([
            'user_id' => $firstUser->id,
            'vendor_id' => $vendor_id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'image' => null, // Save the image name in the database
            'price' => $request->input('price'),
            'old_price' => $request->input('price'),
            'type' => $request->input('type'),
            'commission' => $request->input('commission'),
            'contact_email' => $request->input('contact_email'),
            'vsl_pa_link' => $request->input('vsl_pa_link'),
            'access_link' => $request->input('access_link'),
            'sale_page_link' => $request->input('sale_page_link'),
            'sale_challenge_link' => $request->input('sale_challenge_link'),
            'promotional_material' => $request->input('promotional_material'),
            'is_partnership' => 0,
            'is_affiliated' => 1,
            'x_link' => $request->input('x_link'),
            'ig_link' => $request->input('ig_link'),
            'yt_link' => $request->input('yt_link'),
            'tt_link' => $request->input('tt_link'),
            'fb_link' => $request->input('fb_link'),
            'status' => 'approved',
        ]);

        $product = Product::create($request->all());
        return response()->json($product, 201);
    }

    // Edit a product
    public function update(Request $request, $id)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
        ]);

        // Find the product
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Update the product fields
        $product->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'price' => $validatedData['price'],
        ]);

        return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
    }

    // Delete a product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    // Approve a product
    public function approve($id)
    {
        $product = Product::findOrFail($id);
        $product->status = 'approved'; // Update status to approved
        $product->save();

        return response()->json(['message' => 'Product approved successfully']);
    }
}
