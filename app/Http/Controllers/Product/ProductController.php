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
}
