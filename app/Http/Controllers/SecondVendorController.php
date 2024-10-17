<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Earning;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Flutterwave\Service\Transactions;

class SecondVendorController extends Controller
{

    public function vendorDashboardMetrics(Request $request)
    {
        try {
            // Get authenticated affiliate
            $vendor = Auth::guard('sanctum')->user();

            if (!$vendor) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Optional date filters for metrics
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

            // 4. Total Withdrawals (Sum all withdrawals for the vendor)
            $totalWithdrawals = Withdrawal::where('user_id', $vendor->id)
                ->where('status', 'approved')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // 1. Available vendor Earnings (Total earnings for the vendor)
            $availableEarn = Earning::where('user_id', $vendor->id) // Query Earnings model instead of Transaction
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');  // Sum of earnings amount

            // Calculate available earnings
            $availableEarnings = $totalWithdrawals - $availableEarn;

            // 2. Today's vendor Sales (Sales with vendor for the current day - both count and amount)
            $todaySalesData = Sale::where('vendor_id', $vendor->id) // Query Sales model
                ->whereDate('created_at', Carbon::today())  // Today's sales
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // 3. Total vendor Sales (All-time or filtered by date sales with vendor - both count and amount)
            $totalSalesData = Sale::where('vendor_id', $vendor->id) // Query Sales model
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // Return all data in JSON format
            return response()->json([
                'available_vendor_earnings' => $availableEarnings,
                'todays_vendor_sales' => [
                    'total_amount' => $todaySalesData->total_amount ?? 0,
                    'sale_count' => $todaySalesData->sale_count ?? 0
                ],
                'total_vendor_sales' => [
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

            // 4. Total Withdrawals (Sum all withdrawals for the affiliate)
            $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
                ->where('status', 'approved')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // 1. Available Affiliate Earnings (Total earnings for the affiliate)
            $availableEarn = Earning::where('user_id', $affiliate->id) // Query Earnings model instead of Transaction
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');  // Sum of earnings amount

            // Calculate available earnings
            $availableEarnings = $totalWithdrawals - $availableEarn;

            // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
            $todaySalesData = Sale::where('affiliate_id', $affiliate->aff_id) // Query Sales model
                ->whereDate('created_at', Carbon::today())  // Today's sales
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
            $totalSalesData = Sale::where('affiliate_id', $affiliate->id) // Query Sales model
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

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


    // public function vendorDashboardMetrics(Request $request)
    // {
    //     try {
    //         // Get authenticated vendor
    //         $vendor = auth()->user();

    //         if (!$vendor || !$vendor->is_vendor) {
    //             return response()->json(['error' => 'Unauthorized or not a vendor'], 403);
    //         }

    //         // Optional date filters for metrics
    //         $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    //         $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

    //         // 1. Available Affiliate Earnings (Total earnings for the vendor)
    //         $availableEarnings = Earning::where('user_id', $vendor->id)
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         // 2. Today's Affiliate Sales (Sales with affiliate for the current day)
    //         $todaySales = Sale::where('user_id', $vendor->id)
    //             ->whereNotNull('affiliate_id') // Affiliate sales only
    //             ->whereDate('created_at', Carbon::today())
    //             ->sum('amount');

    //         // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate)
    //         $totalSales = Sale::where('user_id', $vendor->id)
    //             ->whereNotNull('affiliate_id')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         // 4. Total Withdrawals (Sum all withdrawals for the vendor)
    //         $totalWithdrawals = Withdrawal::where('user_id', $vendor->id)
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         // Return all data in JSON format
    //         return response()->json([
    //             'available_affiliate_earnings' => $availableEarnings,
    //             'todays_affiliate_sales' => $todaySales,
    //             'total_affiliate_sales' => $totalSales,
    //             'total_withdrawals' => $totalWithdrawals,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         // Error handling
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }
}
