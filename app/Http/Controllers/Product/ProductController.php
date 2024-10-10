<?php

namespace App\Http\Controllers\Product;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Service\VendorService;
use App\Service\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\OtherProductRequest;
use App\Http\Requests\DigitalProductRequest;

class ProductController extends Controller
{
    protected $productService;
    protected $vendorService;
    public function __construct(ProductService $productService, VendorService $vendorService)
    {
        $this->productService = $productService;
        $this->vendorService = $vendorService;
    }
    
    
        public function getProduct($id, $reffer_id)
        {
            // Check if the user has a referral_id of 0 or null
            $checkUser = User::where('id', $id)
                             ->where(function($query) {
                                 $query->where('refferal_id', 0)
                                       ->orWhereNull('refferal_id');
                             })
                             ->first();
        
            // If user has no referral_id (0 or null), return all products
            if ($checkUser) {
                return Product::all();
            } else {
                // Otherwise, return products by the given referral ID
                return Product::where('refferal_id', $reffer_id)->get();
            }
        }

        public function deleteProduct($id)
        {
            // Find the product by its ID
            $product = Product::find($id);
    
            // Check if the product exists
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found.',
                ], 404);
            }
    
            // Delete the product
            $product->delete();
    
            // Return a success response
            return response()->json([
                'message' => 'Product deleted successfully!',
            ], 200);
        }
    
    public function viewProductsByVendor($vendor_id) {
        // Retrieve all products that match the given vendor_id
        $products = Product::where('vendor_id', $vendor_id)->get();
    
        // Check if products are found
        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'No products found for this vendor.',
            ], 404);
        }
    
        // Return the products as a JSON response
        return response()->json([
            'message' => 'Products retrieved successfully!',
            'products' => $products
        ], 200);
    }
    
     public function viewProductByVendor($vendor_id, $product_id) {
        // Retrieve all products that match the given vendor_id
        $products = Product::where('vendor_id', $vendor_id)->where('id', $product_id)->get();
    
        // Check if products are found
        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'product not found.',
            ], 404);
        }
    
        // Return the products as a JSON response
        return response()->json([
            'message' => 'Product Retrieved!',
            'products' => $products
        ], 200);
    }

        
  public function addProduct(Request $request) {

        $validate = $request->validate([
            '*' => 'sometimes|nullable',
        ]);
        
        
        $image = null;
        
        // Handle the image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
    
            // Generate a unique file name
            $imageName = time() . '.' . $image->getClientOriginalExtension();
    
            // Store the image in the 'public/images' directory
            $image->storeAs('public/images', $imageName);
    
            // Save the image path in the database
            $vendor->image_path = 'images/' . $imageName;
            $vendor->save();
        }


        $vendor = Vendor::where('user_id', $request->user_id)->first();

        if(!$vendor){
            return response()->json([
                'message' => 'vendor not found'
            ]);
        }
        
        
        $vendor_id = $vendor->id;
    
        //Create a new product with the validated data
        $product = Product::create([
            'user_id' => $request->input('user_id'),
            'vendor_id' => $vendor_id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'image' => $image, // Save the image name in the database
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
            'status' => 'pending', // Default status
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Product created successfully!',
            'product' => $product
        ], 201);

}

    public function index(): JsonResponse
    {
        try {
            $products = $this->productService->getAllProducts();
            return $this->success($products, 'All Products!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getApprovedProducts(string $status): JsonResponse
    {
        try {
            $products = $this->productService->getProductsWhereStatus($status);
            return $this->success($products, 'Products with status of approved!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function createDigitalProduct(DigitalProductRequest $digitalProductRequest): JsonResponse
    {
        try {
            $user = $digitalProductRequest->user();
            $productData = $digitalProductRequest->validated();
            $productData['user_id'] = $user->id;
            $digitalProduct = $this->vendorService->newVendorProduct($user->vendor, $productData);
            return $this->success($digitalProduct, 'Digital Product Pending!', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function createOtherProduct(OtherProductRequest $otherProductRequest): JsonResponse
    {
        try {
            $user = $otherProductRequest->user();
            $productData = $otherProductRequest->validated();
            if ($otherProductRequest->hasFile('image') && $otherProductRequest->file('image')->isValid()) {
                $path = $otherProductRequest->file('image')->store('images/products', 'public');
                $productData['image'] = $path;
            } else {
                $productData['image'] = null;
            }
            $productData['user_id'] = $user->id;
            $otherProduct = $this->vendorService->newVendorProduct($user->vendor, $productData);
            $otherProduct['image'] = $otherProduct->image ? Storage::url($otherProduct->image) : null;
            return $this->success($otherProduct, 'Other Product Created!', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error('other product creation failed', ['error' => $th->getMessage()]);
            return $this->error(null, $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function show(int $product): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($product);
            return $this->success($product, 'Single Product!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function edit(int $product, Request $request): JsonResponse
    {
        try {
            $product = $this->productService->updateProductById($product, $request->all());
            return $this->success($product, 'Updated Product!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy(int $product): JsonResponse
    {
        try {
            $product = $this->productService->deleteOneProduct($product);
            return $this->success($product, 'Product Removed!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function unlockMarketAccess(Request $request)
    {
        try {
            $user = $request->user();
            $result = $this->productService->generateMarketAccessPayment($user);
            return $this->success($result, 'unlock market');
        } catch (\Throwable $th) {
            Log::error("unlock market: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }
}
