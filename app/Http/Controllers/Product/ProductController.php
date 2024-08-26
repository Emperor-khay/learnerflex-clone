<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Service\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        try {
            $products = $this->productService->getAllProducts();
            return $this->success($products, 'All Products!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function show(int $product_id)
    {
        try {
            $product = $this->productService->getProductById($product_id);
            return $this->success($product, 'Single Product!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function destroy(int $product_id)
    {
        try {
            $product = $this->productService->deleteOneProduct($product_id);
            return $this->success($product, 'Product Removed!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }
}
