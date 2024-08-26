<?php

namespace App\Service;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function newProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create($data);
        });
    }

    public function getProductById(int|string $id): Product
    {
        return Product::findOrFail($id);
    }

    public function getProductsByUser(int $user_id)
    {
        return Product::where('user_id', $user_id)->get();
    }

    public function getProductsByVendor(int $vendor_id)
    {
        return Product::where('vendor_id', $vendor_id)->get();
    }

    public function getAllProducts()
    {
        return Product::all();
    }

    public function deleteOneProduct(int $id)
    {
        $product = $this->getProductById($id);
        return $product->delete();
    }
}
