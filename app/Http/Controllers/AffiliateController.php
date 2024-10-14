<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Earning;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliateController extends Controller
{

    public function affiliateDashboardMetrics(Request $request)
{
    try {
        // Get authenticated affiliate
        $affiliate = Auth::guard('sanctum')->user();

        if (!$affiliate) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Optional date filters for metrics
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

        // 1. Available Affiliate Earnings (Total earnings for the affiliate)
        $availableEarnings = Transaction::where('affiliate_id', $affiliate->id)
            ->where('status', 'success')  // Only count successful transactions
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('org_aff');  // Sum of affiliate earnings (org_aff)

        // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
        $todaySalesData = Transaction::where('affiliate_id', $affiliate->id)
            ->where('status', 'success')
            ->whereDate('created_at', Carbon::today())  // Today's sales
            ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
            ->first();

        // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
        $totalSalesData = Transaction::where('affiliate_id', $affiliate->id)
            ->where('status', 'success')  // Only successful transactions
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
            ->first();

        // 4. Total Withdrawals (Sum all withdrawals for the affiliate)
        $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('amount');

        // Return all data in JSON format
        return response()->json([
            'available_affiliate_earnings' => $availableEarnings,
            'todays_affiliate_sales' => [
                'total_amount' => $todaySalesData->total_amount ?? 0,
                'sale_count' => $todaySalesData->sale_count ?? 0
            ],
            'total_affiliate_sales' => [
                'total_amount' => $totalSalesData->total_amount ?? 0,
                'sale_count' => $totalSalesData->sale_count ?? 0
            ],
            'total_withdrawals' => $totalWithdrawals,
        ], 200);

    } catch (\Exception $e) {
        // Error handling
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}

    // public function affiliateDashboardMetrics(Request $request)
    // {
    //     try {
    //         // Get authenticated affiliate
    //         $affiliate = Auth::guard('sanctum')->user();
    
    //         if (!$affiliate) {
    //             return response()->json(['error' => 'Unauthorized'], 403);
    //         }
    
    //         // Optional date filters for metrics
    //         $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    //         $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();
    
    //         // 1. Available Affiliate Earnings (Total earnings for the affiliate)
    //         $availableEarnings = Earning::where('user_id', $affiliate->id)
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');
    
    //         // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
    //         $todaySalesData = Sale::where('user_id', $affiliate->id)
    //             ->whereNotNull('affiliate_id') // Affiliate sales only
    //             ->whereDate('created_at', Carbon::today())
    //             ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
    //             ->first();
    
    //         // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
    //         $totalSalesData = Sale::where('user_id', $affiliate->id)
    //             ->whereNotNull('affiliate_id')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
    //             ->first();
    
    //         // 4. Total Withdrawals (Sum all withdrawals for the affiliate)
    //         $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');
    
    //         // Return all data in JSON format
    //         return response()->json([
    //             'available_affiliate_earnings' => $availableEarnings,
    //             'todays_affiliate_sales' => [
    //                 'total_amount' => $todaySalesData->total_amount ?? 0,
    //                 'sale_count' => $todaySalesData->sale_count ?? 0
    //             ],
    //             'total_affiliate_sales' => [
    //                 'total_amount' => $totalSalesData->total_amount ?? 0,
    //                 'sale_count' => $totalSalesData->sale_count ?? 0
    //             ],
    //             'total_withdrawals' => $totalWithdrawals,
    //         ], 200);
    
    //     } catch (\Exception $e) {
    //         // Error handling
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }
}
