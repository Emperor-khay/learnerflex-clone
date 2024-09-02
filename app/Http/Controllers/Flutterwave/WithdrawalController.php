<?php

namespace App\Http\Controllers\Flutterwave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\WithdrawalService;
use Illuminate\Http\JsonResponse;

class WithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Request for latest withdrawals
     */
    public function index(): JsonResponse
    {
        try {
            $withdrawals = $this->withdrawalService->getLatestWithdrawals();
            return $this->success($withdrawals, 'Latest withdrawals!');
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 400);
        }
    }

    /**
     * Request for total sum of withdrawals made by user
     */
    public function userWithdrawSum(Request $request): JsonResponse
    {
        try {
            $totalAmount = $this->withdrawalService->getTotalWithdrawalsByUser($request->user());
            return $this->success($totalAmount, 'Total withdrawals!');
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), 400);
        }
    }
}
