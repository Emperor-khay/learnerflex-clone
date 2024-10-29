<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalProductRequest;
use App\Http\Requests\AlternativeProductRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SuperAdminProductController extends Controller
{
    public function index(Request $request)
    {
        // Get the 'per_page' query parameter or default to 15
        $perPage = $request->get('per_page', 25); // Default is 15 products per page

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


    public function store(AlternativeProductRequest $request)
    {
        try {
            $authUser = auth()->user();

            // Prepare validated data
            $productData = $request->validated();
            $productData['user_id'] = $authUser->id;
            $productData['status'] = 'approved';
            $productData['is_partnership'] = false;
            $productData['is_affiliated'] = true;

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images/products'), $imageName);
                $productData['image'] = asset('images/products/' . $imageName); // Save full URL path
            }

            // Create the product
            $product = Product::create($productData);

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);
        } catch (\Exception $e) {
            // Catch and handle any errors
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    // Edit a product
    public function update(AlternativeProductRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            // Gather input data for update, only updating fields that are present
            $inputData = $request->only([
                'name',
                'description',
                'price',
                'old_price',
                'type',
                'commission',
                'contact_email',
                'vsl_pa_link',
                'access_link',
                'sale_page_link',
                'sale_challenge_link',
                'promotional_material',
                'is_partnership',
                'is_affiliated',
                'x_link',
                'ig_link',
                'yt_link',
                'fb_link',
                'tt_link',
                'status'
            ]);

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('public/products');
                $inputData['image'] = basename($imagePath);
            }

            // Update the product with new data
            $product->update($inputData);

            return response()->json(['message' => 'Product updated successfully', 'product' => $product], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
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
