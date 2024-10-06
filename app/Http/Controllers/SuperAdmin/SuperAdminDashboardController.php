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
    public function getDashboardData()
    {
        // Total counts
        $totalUsers = User::count();
        $totalVendors = Vendor::count();
        $totalProducts = Product::count();

        // Users and vendors per month
        $usersPerMonth = User::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->get();

        $vendorsPerMonth = Vendor::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('month')
            ->get();

        // Sales totals
        $totalSales = Sale::sum('amount');

        $salesPerMonth = Sale::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->get();

        // Recent vendors
        $recentVendors = Vendor::orderBy('created_at', 'desc')->take(6)->get();

        // Transactions
        $transactions = Transaction::all();

        return response()->json([
            'totalUsers' => $totalUsers,
            'totalVendors' => $totalVendors,
            'totalProducts' => $totalProducts,
            'usersPerMonth' => $usersPerMonth,
            'vendorsPerMonth' => $vendorsPerMonth,
            'totalSales' => $totalSales,
            'salesPerMonth' => $salesPerMonth,
            'recentVendors' => $recentVendors,
            'transactions' => $transactions,
        ]);
    }

}
