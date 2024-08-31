<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalProductRequest;
use App\Http\Requests\OtherProductRequest;
use App\Models\Vendor;
use App\Service\ProductService;
use App\Service\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $productService;
    protected $vendorService;
    public function __construct(ProductService $productService, VendorService $vendorService)
    {
        $this->productService = $productService;
        $this->vendorService = $vendorService;
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

    public function createDigitalProduct(Vendor $vendor, DigitalProductRequest $digitalProductRequest): JsonResponse
    {
        try {
            $user = $digitalProductRequest->user();
            $productData = $digitalProductRequest->validated();
            $productData['user_id'] = $user->id;
            $digitalProduct = $this->vendorService->newVendorProduct($vendor, $productData);
            return $this->success($digitalProduct, 'Digital Product Pending!', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function createOtherProduct(Vendor $vendor, OtherProductRequest $otherProductRequest): JsonResponse
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
            $otherProduct = $this->vendorService->newVendorProduct($vendor, $productData);
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
}
