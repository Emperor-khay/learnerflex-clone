<?php

namespace App\Service;

use App\Http\Controllers\Flutterwave\PaymentController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected $userService;
    /**
     * Create a new class instance.
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(array $data)
    {
        try {
            DB::beginTransaction();
            $data['password'] = Hash::make($data['password']);
            $user = $this->userService->newUser($data);
            $paymentUrl = $this->generateOnboardPaymentLink($user);
            DB::commit();
            return [
                'user' => $user,
                'payment_url' => $paymentUrl
            ];
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    private function generateOnboardPaymentLink($user)
    {
        $paymentData = [
            'tx_ref' => uniqid().time(),
            'amount' => 5100, // Amount to be charged
            'currency' => 'NGN',
            'redirect_url' => url('/payment/callback'),
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
                'phone_number' => $user->phone,
            ],
            'customizations' => [
                'title' => 'Payment for Registration',
                'description' => 'Complete your registration payment',
            ]
        ];
        $transaction = [
            'tx_ref' => $paymentData['tx_ref'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
        ];

        $this->userService->createTransactionForUser($user, $transaction);

        // Initialize Flutterwave payment
        $flutterwave = new PaymentController();
        $payment = $flutterwave->initiatePayment($paymentData);

        // Extracting the data from the JsonResponse
        $paymentArray = $payment->getData(true); // 'true' converts the object to an array

        return $paymentArray['data']['link'];
    }

    public function login(string $email, string $password)
    {
        $user = $this->userService->getUserByEmail($email);
        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Credentials Invalid', 422);
        }

        return [
            'user' => $user
        ];
    }

    public function logout($user): bool
    {
        if (!$user->tokens()->where('id', $user->currentAccessToken()->id)->delete()) {
            throw new \Exception();
        }
        return true;
    }
}
