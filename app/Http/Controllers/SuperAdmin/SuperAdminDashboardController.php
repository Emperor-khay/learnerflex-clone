<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SuperAdminDashboardController extends Controller
{
    // public function getDashboardData()
    // {
    //     // Total counts
    //     $totalUsers = User::count();
    //     $totalVendors = Vendor::count();
    //     $totalProducts = Product::count();

    //     // Users and vendors per month
    //     $usersPerMonth = User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
    //         ->groupBy('month')
    //         ->get();

    //     $vendorsPerMonth = Vendor::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
    //         ->groupBy('month')
    //         ->get();

    //     // Sales totals
    //     $totalSales = Sale::sum('amount');

    //     $salesPerMonth = Sale::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
    //         ->groupBy('month')
    //         ->get();

    //     // Recent vendors
    //     $recentVendors = Vendor::orderBy('created_at', 'desc')->take(6)->get();

    //     // Transactions
    //     $transactions = Transaction::all();

    //     return response()->json([
    //         'totalUsers' => $totalUsers,
    //         'totalVendors' => $totalVendors,
    //         'totalProducts' => $totalProducts,
    //         'usersPerMonth' => $usersPerMonth,
    //         'vendorsPerMonth' => $vendorsPerMonth,
    //         'totalSales' => $totalSales,
    //         'salesPerMonth' => $salesPerMonth,
    //         'recentVendors' => $recentVendors,
    //         'transactions' => $transactions,
    //     ]);
    // }

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
    })->sum('amount');

    // Number of sales (filtered by month/year or overall)
    $totalSalesCount = Sale::when($month, function ($query) use ($month) {
        return $query->whereMonth('created_at', $month);
    })->when($year, function ($query) use ($year) {
        return $query->whereYear('created_at', $year);
    })->count();

    // Sum of org_company, org_vendor, org_aff (filtered by month/year or overall)
    $orgData = Transaction::selectRaw('SUM(org_company) as org_company, SUM(org_vendor) as org_vendor, SUM(org_aff) as org_aff')
    ->where('status', 'completed') 
        ->when($month, function ($query) use ($month) {
            return $query->whereMonth('created_at', $month);
        })
        ->when($year, function ($query) use ($year) {
            return $query->whereYear('created_at', $year);
        })
        ->first();

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
        'org_company' => $orgData->org_company,
        'org_vendor' => $orgData->org_vendor,
        'org_aff' => $orgData->org_aff,
    ]);
}


}
