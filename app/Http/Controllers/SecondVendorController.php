<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Vendor;
use App\Models\Earning;
use App\Models\Product;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Flutterwave\Service\Transactions;
use Nette\Schema\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UserProfileUpdateRequest;

class SecondVendorController extends Controller
{

    //working I just added conversions
    // public function vendorDashboardMetrics(Request $request)
    // {
    //     try {
    //         // Get authenticated vendor
    //         $vendor = Auth::guard('sanctum')->user();

    //         if (!$vendor) {
    //             return response()->json(['error' => 'Unauthorized'], 403);
    //         }

    //         // Optional date filters for metrics
    //         $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    //         $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

    //         // Total Vendor Withdrawals
    //         $vendorWithdrawals = Withdrawal::where('user_id', $vendor->id)
    //             ->where('type', 'vendor') // Only vendor withdrawals
    //             ->where('status', 'approved')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         // Available Vendor Earnings
    //         $vendorEarn = Sale::where('vendor_id', $vendor->id)
    //             ->where('status', 'success')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('org_vendor');
    //         $availableVendorEarnings = $vendorEarn - $vendorWithdrawals;

    //         $affiliateWithdrawals = Withdrawal::where('user_id', $vendor->id)
    //             ->where('type', 'affiliate') // Only vendor withdrawals
    //             ->where('status', 'approved')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         $affiliateEarn = Sale::where('affiliate_id', $vendor->aff_id)
    //             ->whereNotNull('product_id')
    //             ->where('status', 'success')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('org_aff');

    //         $availableAffiliateEarnings = $affiliateEarn - $affiliateWithdrawals;

    //         \Log::info('Vendor Earn', ['vendorEarn' => $vendorEarn]);
    //         \Log::info('Available Affiliate Earnings', ['availableAffiliateEarnings' => $availableAffiliateEarnings]);


    //         $totalProductEarnings = $vendorEarn + $availableAffiliateEarnings;
    //         \Log::info('total product Earn', ['total product earn' => $totalProductEarnings]);


    //         // Today's Vendor Sales
    //         $todaySalesData = Sale::where('vendor_id', $vendor->id)
    //             ->where('status', 'success')
    //             ->whereDate('created_at', Carbon::today())
    //             ->selectRaw('COUNT(*) as sale_count, SUM(org_vendor) as total_amount')
    //             ->first();

    //         // Total Vendor Sales
    //         $totalSalesData = Sale::where('vendor_id', $vendor->id)
    //             ->where('status', 'success')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->selectRaw('COUNT(*) as sale_count, SUM(org_vendor) as total_amount')
    //             ->first();

    //         // Return all data in JSON format
    //         return response()->json([
    //             'available_vendor_earnings' => $availableVendorEarnings,
    //             'total_product_earnings' => $totalProductEarnings,
    //             'todays_vendor_sales' => [
    //                 'total_amount' => $todaySalesData->total_amount ?? 0,
    //                 'sale_count' => $todaySalesData->sale_count ?? 0
    //             ],
    //             'total_vendor_sales' => [
    //                 'total_amount' => $totalSalesData->total_amount ?? 0,
    //                 'sale_count' => $totalSalesData->sale_count ?? 0
    //             ],
    //             'total_withdrawals' => $vendorWithdrawals,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         // Error handling
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }

    public function vendorDashboardMetrics(Request $request)
{
    try {
        // Get authenticated vendor
        $vendor = Auth::guard('sanctum')->user();

        if (!$vendor) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Optional date filters for metrics
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

        // Total Vendor Withdrawals
        $vendorWithdrawals = Withdrawal::where('user_id', $vendor->id)
            ->where('type', 'vendor') // Only vendor withdrawals
            ->where('status', 'approved')
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('amount');

        // Available Vendor Earnings
        $vendorEarn = Sale::where('vendor_id', $vendor->id)
            ->where('status', 'success')
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('org_vendor');
        $availableVendorEarnings = $vendorEarn - $vendorWithdrawals;

        // Affiliate Earnings and Withdrawals
        $affiliateWithdrawals = Withdrawal::where('user_id', $vendor->id)
            ->where('type', 'affiliate') // Only affiliate withdrawals
            ->where('status', 'approved')
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('amount');

        $affiliateEarn = Sale::where('affiliate_id', $vendor->aff_id)
            ->whereNotNull('product_id')
            ->where('status', 'success')
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->sum('org_aff');

        $availableAffiliateEarnings = $affiliateEarn - $affiliateWithdrawals;

        // Total Product Earnings
        $totalProductEarnings = $vendorEarn + $availableAffiliateEarnings;

        // Today's Vendor Sales
        $todaySalesData = Sale::where('vendor_id', $vendor->id)
            ->where('status', 'success')
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('COUNT(*) as sale_count, SUM(org_vendor) as total_amount')
            ->first();

        // Total Vendor Sales
        $totalSalesData = Sale::where('vendor_id', $vendor->id)
            ->where('status', 'success')
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->selectRaw('COUNT(*) as sale_count, SUM(org_vendor) as total_amount')
            ->first();

        // Convert monetary values to naira
        $availableVendorEarningsNaira = $availableVendorEarnings / 100;
        $totalProductEarningsNaira = $totalProductEarnings / 100;
        $vendorWithdrawalsNaira = $vendorWithdrawals / 100;
        $todaysTotalAmountNaira = ($todaySalesData->total_amount ?? 0) / 100;
        $totalSalesAmountNaira = ($totalSalesData->total_amount ?? 0) / 100;

        // Return all data in JSON format
        return response()->json([
            'available_vendor_earnings' => $availableVendorEarningsNaira,
            'total_product_earnings' => $totalProductEarningsNaira,
            'todays_vendor_sales' => [
                'total_amount' => $todaysTotalAmountNaira,
                'sale_count' => $todaySalesData->sale_count ?? 0,
            ],
            'total_vendor_sales' => [
                'total_amount' => $totalSalesAmountNaira,
                'sale_count' => $totalSalesData->sale_count ?? 0,
            ],
            'total_withdrawals' => $vendorWithdrawalsNaira,
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

            // 4. Total Withdrawals (Sum all withdrawals for the user)
            $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
                ->where('status', 'approved')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // 1. Available Affiliate Earnings (Total earnings for the affiliate)
            $availableEarn = Sale::where('affiliate_id', $affiliate->aff_id)
                ->whereNotNull('product_id')
                ->where('status', 'success') // Transaction must be successful
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('org_aff');  // Sum of earnings amount (affiliate share)

            // Calculate available earnings
            $availableEarnings = $availableEarn - $totalWithdrawals;

            // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
            $todaySalesData = Sale::where('affiliate_id', $affiliate->aff_id)
                ->where('status', 'success') // Query Sales model
                ->whereDate('created_at', Carbon::today())  // Today's sales
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
            $totalSalesData = Sale::where('affiliate_id', $affiliate->aff_id) // Fixed to use aff_id
                ->where('status', 'success')
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

    public function salesAffiliate()
    {
        $user = Auth::user();
        $totalNoSales = Transaction::whereNotNull('affiliate_id')->where('vendor_id', $user->id)->where('status', 'success')->count();


        return response()->json([
            'message' => "affilaite number of sales",
            'success' => true,
            'no of sales' => $totalNoSales
        ]);
    }

    public function handleUserProfile(UserProfileUpdateRequest $request): JsonResponse
    {
        try {
            // Get the authenticated user
            $user = auth()->user();

            // Prepare data for update
            $data = $request->validated();

            // Handle image upload if provided
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Store the image in the public directory and get the relative path
                $imagePath = $request->file('image')->store('images/users', 'public');

                // Delete old image if exists
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }

                // Get the full URL and save it in the database
                // $data['image'] = Storage::url($imagePath);
                // Save the relative path in the database
                $data['image'] = $imagePath;
            }

            // Handle optional currency field
            if (!$request->filled('currency')) {
                unset($data['currency']);
            }

            // Update user details
            $user->update($data);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user,
                'image_url' => $user->image // Full URL is saved and returned directly
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating profile',
                'error' => $e->getMessage()
            ], 500);
            Log::error('updating profile', ['error' => $e->getMessage()]);
        }
        \Log::error('updating profile', ['error' => $e->getMessage()]);
    }


    public function createOrUpdateVendor(Request $request)
    {
        try {
            // Retrieve the authenticated user
            $user = Auth::user();

            // Check if user has the 'vendor' role
            if ($user->role !== 'vendor') {
                return response()->json(['message' => 'Only vendors can access this route'], 403);
            }

            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5048',
                'description' => 'nullable|string',
                'x_link' => 'nullable|url',
                'ig_link' => 'nullable|url',
                'yt_link' => 'nullable|url',
                'fb_link' => 'nullable|url',
                'tt_link' => 'nullable|url',
                'display' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Find the existing vendor record associated with the user or create a new one
            $vendor = Vendor::firstOrCreate(['user_id' => $user->id]);

            // Only update fields if they are present in the request
            $vendor->fill([
                'name' => $request->name ?? $vendor->name,
                'description' => $request->description ?? $vendor->description,
                'x_link' => $request->x_link ?? $vendor->x_link,
                'ig_link' => $request->ig_link ?? $vendor->ig_link,
                'yt_link' => $request->yt_link ?? $vendor->yt_link,
                'fb_link' => $request->fb_link ?? $vendor->fb_link,
                'tt_link' => $request->tt_link ?? $vendor->tt_link,
                'display' => $request->display ?? $vendor->display,
            ]);

            // Handle photo upload if provided
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('vendor_photos', 'public');
                $vendor->photo = $photoPath;
            }

            // Save updated vendor information
            $vendor->save();

            // Get the full URL for the photo
            $vendor->photo = $vendor->photo ? Storage::url($vendor->photo) : null;

            return response()->json([
                'message' => $vendor->wasRecentlyCreated ? 'Vendor profile created successfully' : 'Vendor profile updated successfully',
                'vendor' => $vendor
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            // Validate incoming request data
            $validator = Validator::make($request->all(), [
                'old_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:6', 'confirmed'], // Confirmed ensures a `new_password_confirmation` field matches
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated(); // Get the validated data

            $user = auth()->user(); // Get the currently authenticated user

            // Check if the old password is correct
            if (!Hash::check($validated['old_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'old_password' => ['The provided old password is incorrect.'],
                ]);
            }

            // Update the password
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            return response()->json([
                'message' => 'Password changed successfully.',
            ], Response::HTTP_OK);
        } catch (ValidationException $e) {
            // Return validation errors
            return response()->json([
                'message' => 'Validation error occurred.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            \Log::error('Password change failed', ['error' => $th->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while changing the password.',
                'error' => $th->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
