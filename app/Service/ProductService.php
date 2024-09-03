<?php

namespace App\Service;

use App\Http\Controllers\Flutterwave\PaymentController;
use App\Models\Product;
use App\Service\UserService;
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
        return Product::with('vendor')->findOrFail($id);
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

    public function getProductsWhereStatus(string $status)
    {
        return Product::with('vendor')->where('status', $status)->get();
    }

    public function updateProductById($product_id, array $updatedData)
    {
        $product = $this->getProductById($product_id);
        return DB::transaction(function () use ($product, $updatedData) {
            return $product->update($updatedData);
        });
    }

    public function deleteOneProduct(int $id)
    {
        $product = $this->getProductById($id);
        return $product->delete();
    }

    public function generateMarketAccessPayment($user)
    {
        $paymentData = [
            'tx_ref' => uniqid().time(),
            'amount' => 1100, // Amount to be charged
            'currency' => 'NGN',
            'redirect_url' => url('/payment/market-access/callback'),
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
                'phone_number' => $user->phone,
            ],
            'customizations' => [
                'title' => 'Unlock Market',
                'description' => 'Unlocks all products for your access to promote',
            ]
        ];

        $transaction = [
            'tx_ref' => $paymentData['tx_ref'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
        ];

        $userService = new userService();

        $userService->createTransactionForUser($user, $transaction);

        // Initialize Flutterwave payment
        $flutterwave = new PaymentController();
        $payment = $flutterwave->initiatePayment($paymentData);

        // Extracting the data from the JsonResponse
        $paymentArray = $payment->getData(true); // 'true' converts the object to an array

        return $paymentArray['data']['link'];
    }
}
