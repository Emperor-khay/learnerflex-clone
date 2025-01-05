<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Sale;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SuperAdminDashboardController extends Controller
{

    // public function getDashboardData(Request $request)
    // {
    //     // Optional: Filter by month and/or year if provided
    //     $month = $request->input('month');
    //     $year = $request->input('year');

    //     // Total counts of users (no filtering, overall)
    //     $totalUsers = User::count();
    //     $totalVendors = User::where('role', 'vendor')->count();
    //     $totalProducts = Product::count();

    //     // Count users by roles (affiliate, vendor, admin)
    //     $totalAffiliates = User::where('role', 'affiliate')->count();
    //     $totalAdmins = User::where('role', 'admin')->count();

    //     // Users and vendors per month and year
    //     $usersPerMonth = User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
    //         ->when($month, function ($query) use ($month) {
    //             return $query->whereMonth('created_at', $month);
    //         })
    //         ->when($year, function ($query) use ($year) {
    //             return $query->whereYear('created_at', $year);
    //         })
    //         ->groupBy('month')
    //         ->get();

    //     // Sales totals (filter by month/year if provided, otherwise return overall)
    //     $totalSales = Sale::when($month, function ($query) use ($month) {
    //         return $query->whereMonth('created_at', $month);
    //     })->when($year, function ($query) use ($year) {
    //         return $query->whereYear('created_at', $year);
    //     })->sum('amount');

    //     // Number of sales (filtered by month/year or overall)
    //     $totalSalesCount = Sale::when($month, function ($query) use ($month) {
    //         return $query->whereMonth('created_at', $month);
    //     })->when($year, function ($query) use ($year) {
    //         return $query->whereYear('created_at', $year);
    //     })->count();

    //     // Sum of org_company, org_vendor, org_aff (filtered by month/year or overall)
    //     $orgData = Transaction::selectRaw('SUM(org_company) as org_company, SUM(org_vendor) as org_vendor, SUM(org_aff) as org_aff')
    //         ->where('status', 'completed')
    //         ->when($month, function ($query) use ($month) {
    //             return $query->whereMonth('created_at', $month);
    //         })
    //         ->when($year, function ($query) use ($year) {
    //             return $query->whereYear('created_at', $year);
    //         })
    //         ->first();

    //     // Returning both filtered and overall data
    //     return response()->json([
    //         'totalUsers' => $totalUsers,
    //         'totalVendors' => $totalVendors,
    //         'totalProducts' => $totalProducts,
    //         'totalAffiliates' => $totalAffiliates,
    //         'totalAdmins' => $totalAdmins,
    //         'usersPerMonth' => $usersPerMonth,
    //         'totalSales' => $totalSales,
    //         'totalSalesCount' => $totalSalesCount,
    //         'org_company' => $orgData->org_company,
    //         'org_vendor' => $orgData->org_vendor,
    //         'org_aff' => $orgData->org_aff,
    //     ]);
    // }

    // public function analytics(Request $request)
    // {
    //     try {
    //         // Revenue and Count from Marketplace Unlocks
    //         $marketplaceUnlocks = Transaction::whereNull('vendor_id')
    //             ->where('description', 'marketplace_unlock')
    //             ->where('status', 'success');

    //         $marketplaceRevenue = $marketplaceUnlocks->sum('amount');
    //         $marketplaceCount = $marketplaceUnlocks->count();

    //         // Revenue and Count from Signups
    //         $signups = Transaction::whereNull('vendor_id')
    //             ->where('description', 'signup_fee')
    //             ->where('status', 'success');

    //         $signupRevenue = $signups->sum('amount');
    //         $signupCount = $signups->count();

    //         // Total Revenue Generated (Product Sales + Signups + Marketplace Unlocks)
    //         $productSales = Sale::whereNotNull('product_id')
    //             ->whereNotNull('vendor_id')
    //             ->where('status', 'success');

    //         $productSalesRevenue = $productSales->sum('amount');
    //         $productSalesCount = $productSales->count();

    //         $totalRevenue = $productSalesRevenue + $signupRevenue + $marketplaceRevenue;

    //         // Today's Earnings
    //         $orgEarningsToday = Sale::whereDate('created_at', today())->sum('org_company');
    //         $orgEarningsTodayCount = Sale::whereDate('created_at', today())->count();

    //         $affiliateEarningsToday = Sale::whereDate('created_at', today())->sum('org_aff');
    //         $affiliateEarningsTodayCount = Sale::whereDate('created_at', today())
    //             ->whereNotNull('affiliate_id') // Filter for affiliates
    //             ->count();

    //         $vendorEarningsToday = Sale::whereDate('created_at', today())->sum('org_vendor');
    //         $vendorEarningsTodayCount = Sale::whereDate('created_at', today())->count();

    //         // Unpaid Balances
    //         $unpaidAffiliateBalance = Sale::sum('org_aff') // Total owed
    //             - Withdrawal::whereHas('user', function ($query) {
    //                 $query->where('role', 'affiliate');
    //             })
    //             ->where('status', 'approved')
    //             ->sum('amount'); // Total withdrawn

    //         $unpaidVendorBalance = Sale::sum('org_vendor') // Total owed
    //             - Withdrawal::whereHas('user', function ($query) {
    //                 $query->where('role', 'vendor');
    //             })
    //             ->where('status', 'approved')
    //             ->sum('amount'); // Total withdrawn

    //         // Total Payouts
    //         $affiliatePayouts = Withdrawal::whereHas('user', function ($query) {
    //             $query->where('role', 'affiliate');
    //         })
    //             ->where('status', 'approved')
    //             ->sum('amount');

    //         $vendorPayouts = Withdrawal::whereHas('user', function ($query) {
    //             $query->where('role', 'vendor');
    //         })
    //             ->where('status', 'approved')
    //             ->sum('amount');

    //         // Response Data
    //         return response()->json([
    //             'total_revenue' => $totalRevenue,
    //             'product_sales_revenue' => $productSalesRevenue,
    //             'product_sales_count' => $productSalesCount,
    //             'marketplace_revenue' => $marketplaceRevenue,
    //             'marketplace_count' => $marketplaceCount,
    //             'signup_revenue' => $signupRevenue,
    //             'signup_count' => $signupCount,
    //             'org_earnings_today' => [
    //                 'amount' => $orgEarningsToday,
    //                 'count' => $orgEarningsTodayCount,
    //             ],
    //             'affiliate_earnings_today' => [
    //                 'amount' => $affiliateEarningsToday,
    //                 'count' => $affiliateEarningsTodayCount,
    //             ],
    //             'vendor_earnings_today' => [
    //                 'amount' => $vendorEarningsToday,
    //                 'count' => $vendorEarningsTodayCount,
    //             ],
    //             'unpaid_affiliate_balance' => $unpaidAffiliateBalance,
    //             'unpaid_vendor_balance' => $unpaidVendorBalance,
    //             'total_affiliate_payouts' => $affiliatePayouts,
    //             'total_vendor_payouts' => $vendorPayouts,
    //         ]);
    //     } catch (\Exception $e) {
    //         // Error Handling
    //         return response()->json([
    //             'error' => 'Failed to retrieve analytics data',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function analytics(Request $request)
    {
        try {
            // Revenue and Count from Marketplace Unlocks
            $marketplaceUnlocks = Transaction::whereNull('vendor_id')
                ->where('description', 'marketplace_unlock')
                ->where('status', 'success');

            $marketplaceRevenue = $marketplaceUnlocks->sum('amount') / 100;
            $marketplaceCount = $marketplaceUnlocks->count();

            // Revenue and Count from Signups
            $signups = Transaction::whereNull('vendor_id')
                ->where('description', 'signup_fee')
                ->where('status', 'success');

            $signupRevenue = $signups->sum('amount') / 100;
            $signupCount = $signups->count();

            // Total Revenue Generated (Product Sales + Signups + Marketplace Unlocks)
            $productSales = Transaction::whereNotNull('product_id')
                ->whereNotNull('vendor_id')
                ->where('status', 'success');

            $productSalesRevenue = $productSales->sum('amount') / 100;
            $productSalesCount = $productSales->count();

            $totalRevenue = $productSalesRevenue + $signupRevenue + $marketplaceRevenue;

            // Today's Earnings
            $orgEarningsToday = Sale::whereDate('created_at', today())->sum('org_company') / 100;
            $orgEarningsTodayCount = Sale::whereDate('created_at', today())->count();

            $affiliateEarningsToday = Sale::whereDate('created_at', today())->sum('org_aff') / 100;
            $affiliateEarningsTodayCount = Sale::whereDate('created_at', today())
                ->whereNotNull('affiliate_id') // Filter for affiliates
                ->count();

            $vendorEarningsToday = Sale::whereDate('created_at', today())->sum('org_vendor') / 100;
            $vendorEarningsTodayCount = Sale::whereDate('created_at', today())->count();

            // Unpaid Balances
            // Unpaid Affiliate Balance
            $unpaidAffiliateBalance = (Sale::sum('org_aff') // Total owed to affiliates
                - Withdrawal::where('type', 'affiliate') // Focus on withdrawal type
                ->where('status', 'approved') // Only approved withdrawals
                ->sum('amount')) / 100;

            // Unpaid Vendor Balance
            $unpaidVendorBalance = (Sale::sum('org_vendor') // Total owed to vendors
                - Withdrawal::where('type', 'vendor') // Focus on withdrawal type
                ->where('status', 'approved') // Only approved withdrawals
                ->sum('amount')) / 100;


            // Total Affiliate Payouts
            $affiliatePayouts = Withdrawal::where('type', 'affiliate') // Focus on withdrawal type
                ->where('status', 'approved') // Only approved withdrawals
                ->sum('amount') / 100;

            // Total Vendor Payouts
            $vendorPayouts = Withdrawal::where('type', 'vendor') // Focus on withdrawal type
                ->where('status', 'approved') // Only approved withdrawals
                ->sum('amount') / 100;


            // Response Data
            return response()->json([
                'total_revenue' => $totalRevenue,
                'product_sales_revenue' => $productSalesRevenue,
                'product_sales_count' => $productSalesCount,
                'marketplace_revenue' => $marketplaceRevenue,
                'marketplace_count' => $marketplaceCount,
                'signup_revenue' => $signupRevenue,
                'signup_count' => $signupCount,
                'org_earnings_today' => [
                    'amount' => $orgEarningsToday,
                    'count' => $orgEarningsTodayCount,
                ],
                'affiliate_earnings_today' => [
                    'amount' => $affiliateEarningsToday,
                    'count' => $affiliateEarningsTodayCount,
                ],
                'vendor_earnings_today' => [
                    'amount' => $vendorEarningsToday,
                    'count' => $vendorEarningsTodayCount,
                ],
                'unpaid_affiliate_balance' => $unpaidAffiliateBalance,
                'unpaid_vendor_balance' => $unpaidVendorBalance,
                'total_affiliate_payouts' => $affiliatePayouts,
                'total_vendor_payouts' => $vendorPayouts,
            ]);
        } catch (\Exception $e) {
            // Error Handling
            return response()->json([
                'error' => 'Failed to retrieve analytics data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDashboardData(Request $request)
    {
        // Optional: Filter by month and/or year if provided
        $month = $request->input('month');
        $year = $request->input('year');

        // Total counts of users (no filtering, overall)
        $totalUsers = User::count();
        $totalVendors = User::where('role', 'vendor')->count();
        $totalProducts = Product::count();

        // Count users by roles (affiliate, vendor, admin)
        $totalAffiliates = User::where('role', 'affiliate')->count();
        $totalAdmins = User::where('role', 'admin')->count();

        // Users and vendors per month and year
        $usersPerMonth = User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->when($month, function ($query) use ($month) {
                return $query->whereMonth('created_at', $month);
            })
            ->when($year, function ($query) use ($year) {
                return $query->whereYear('created_at', $year);
            })
            ->groupBy('month')
            ->get();

        // Sales totals (filter by month/year if provided, otherwise return overall)
        $totalSales = Sale::when($month, function ($query) use ($month) {
            return $query->whereMonth('created_at', $month);
        })->when($year, function ($query) use ($year) {
            return $query->whereYear('created_at', $year);
        })->sum('amount') / 100; // Convert to naira

        // Number of sales (filtered by month/year or overall)
        $totalSalesCount = Sale::when($month, function ($query) use ($month) {
            return $query->whereMonth('created_at', $month);
        })->when($year, function ($query) use ($year) {
            return $query->whereYear('created_at', $year);
        })->count();

        // Sum of org_company, org_vendor, org_aff (filtered by month/year or overall)
        $orgData = Transaction::selectRaw('SUM(org_company) as org_company, SUM(org_vendor) as org_vendor, SUM(org_aff) as org_aff')
            ->where('status', 'success')
            ->when($month, function ($query) use ($month) {
                return $query->whereMonth('created_at', $month);
            })
            ->when($year, function ($query) use ($year) {
                return $query->whereYear('created_at', $year);
            })
            ->first();

        // Convert org_company, org_vendor, and org_aff to naira
        $orgCompany = $orgData->org_company / 100;
        $orgVendor = $orgData->org_vendor / 100;
        $orgAff = $orgData->org_aff / 100;

        // Returning both filtered and overall data
        return response()->json([
            'totalUsers' => $totalUsers,
            'totalVendors' => $totalVendors,
            'totalProducts' => $totalProducts,
            'totalAffiliates' => $totalAffiliates,
            'totalAdmins' => $totalAdmins,
            'usersPerMonth' => $usersPerMonth,
            'totalSales' => $totalSales,
            'totalSalesCount' => $totalSalesCount,
            'org_company' => $orgCompany,
            'org_vendor' => $orgVendor,
            'org_aff' => $orgAff,
        ]);
    }
}
