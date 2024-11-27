<?php

namespace App\Http\Controllers\Vendor;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Service\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Service\VendorService;
use App\Mail\VendorAccountWanted;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\OtherProductRequest;
use App\Http\Requests\DigitalProductRequest;
use App\Http\Requests\UpdateOtherProductRequest;
use App\Http\Requests\UpdateDigitalProductRequest;



class VendorController extends Controller
{
    protected $userService;
    protected $vendorService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(User $user): JsonResponse
    {
        try {
            $vendor = $this->userService->getUserVendor($user);
            return $this->success($vendor, 'Retrieved user vendor data!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function sendVendorRequest(Request $request)
    {
        $validate = $request->validate([
            'email' => 'bail|required|string',
            'sale_url' => 'required|string',
        ]);

        $user = User::where('email', $validate['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Not a user. Cannot request for vendor'], 400);
        }


        $user_id = $user->id;
        $saleurl = $validate['sale_url'];

        DB::table('vendor_status')->insert([
            'user_id' => $user_id,
            'sale_url' => $validate['sale_url'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        Mail::to('learnerflexltd@gmail.com')->send(new VendorAccountWanted($user, $saleurl));

        return response()->json(['success' => true, 'message' => 'Vendor Request sent successfully'], 201);
    }

    // public function store(VendorRequest $request): JsonResponse
    // {
    //     try {
    //         $validatedData = $request->validated();
    //         if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
    //             $path = $request->file('photo')->store('images/vendors', 'public');
    //             $validatedData['photo'] = $path;
    //         } else {
    //             $validatedData['photo'] = null;
    //         }
    //         $user = $request->user();
    //         $result = $this->userService->createVendorForUser($user, $validatedData);
    //         $result['photo'] = $result->photo ? Storage::url($result->photo) : null;
    //         return $this->success($result, 'Vendor Created!', Response::HTTP_CREATED);
    //     } catch (\Throwable $th) {
    //         Log::error('Vendor creation failed', ['error' => $th->getMessage()]);
    //         return $this->error(null, $th->getMessage(), Response::HTTP_BAD_REQUEST);
    //     }
    // }

    public function delete(int $vendor_id): JsonResponse
    {
        try {
            $vendor = $this->vendorService->deleteVendor($vendor_id);
            return $this->success($vendor, 'deleted vendor!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if the user exists
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if the user is a vendor
        $vendor = Vendor::where('user_id', $user->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'User is not a vendor'], 403);
        }

        // Generate a token or handle login logic
        $token = $user->createToken('VendorAuthToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'vendor' => $vendor
        ]);
    }

    public function getVendorSales(Request $request)
    {
        // Get the authenticated user's ID
        $userId = $request->user()->id;

        // Fetch the sales for the authenticated vendor with pagination
        $paginatedSales = Transaction::where('vendor_id', $userId)
            ->where('status', 'success')
            ->whereNotNull('product_id')
            ->paginate(20); // Adjust the number 10 to change the number of items per page

        return response()->json([
            'success' => true,
            'message' => 'Transaction successful. Sales retrieved.',
            'data' => $paginatedSales->items(), // The sales data
            'pagination' => [
                'current_page' => $paginatedSales->currentPage(),
                'last_page' => $paginatedSales->lastPage(),
                'total' => $paginatedSales->total(),
                'per_page' => $paginatedSales->perPage(),
            ]
        ]);
    }


    public function getVendorTotalSaleAmount($id)
    {
        $totalAmount = Sale::where('user_id', $id)->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'total amount sales made',
            'Total sale' => $totalAmount
        ]);
    }

    public function vendorEarnings($id)
    {
        $totalAmount = Transaction::where('user_id', $id)->sum('org_vendor');

        return response()->json([
            'success' => true,
            'message' => 'total earnings for withdrawal',
            'Total sale' => $totalAmount
        ]);
    }

    public function productPerformance(Request $request)
    {
        $vendorId = auth()->id(); // Assuming the vendor is authenticated

        // Fetch vendor's product metrics
        $productMetrics = [
            'total_products_sold' => DB::table('sales')
                ->where('vendor_id', $vendorId)
                ->where('status', 'success') // Assuming 'status' filters for successful sales
                ->count(),
            'total_affiliates' => DB::table('sales')
                ->where('vendor_id', $vendorId)
                ->distinct('affiliate_id')
                ->count('affiliate_id'),
            'total_products' => DB::table('products')
                ->where('user_id', $vendorId)
                ->count(),
        ];

        $affiliateMetrics = DB::table('sales')
            ->join('users as affiliates', 'sales.affiliate_id', '=', 'affiliates.aff_id') // Match with aff_id
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->select(
                'affiliates.aff_id as affiliate_id', // Fetch affiliate's unique aff_id
                'affiliates.name as affiliate_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('COUNT(sales.id) as sales_count') // Count of successful sales for the affiliate
            )
            ->where('sales.vendor_id', $vendorId) // Filter for the current vendor
            ->whereNotNull('sales.affiliate_id') // Ensure the sale has an affiliate
            ->where('sales.status', 'success') // Include only successful sales
            ->groupBy('affiliates.aff_id', 'affiliates.name', 'products.id', 'products.name')
            ->get();



        return response()->json([
            'success' => true,
            'product_metrics' => $productMetrics,
            'affiliate_metrics' => $affiliateMetrics,
        ]);
    }


    public function viewAffiliatePerformance($affiliateId)
    {
        $vendorId = auth()->id(); // Assuming the vendor is authenticated

        // Get affiliate's general details
        $affiliateDetails = DB::table('users')
            ->where('aff_id', $affiliateId)
            ->select('name', 'email', 'phone', 'country', 'created_at')
            ->first();

        // Calculate total sales the affiliate made for this vendor's products
        $totalSales = DB::table('sales')
            ->where('affiliate_id', $affiliateId)
            ->where('vendor_id', $vendorId)
            ->where('status', 'success')
            ->count();

        return response()->json([
            'success' => true,
            'affiliate_details' => [
                'name' => $affiliateDetails->name,
                'email' => $affiliateDetails->email,
                'phone' => $affiliateDetails->phone,
                'country' => $affiliateDetails->country,
                'total_sales' => $totalSales,
                'registered_on' => Carbon::parse($affiliateDetails->created_at)->format('Y-m-d'),
            ]
        ]);
    }



    public function getAffDetails($aff_id)
    {

        $user  = User::find($aff_id);

        return response()->json([
            'message' => 'User Info',
            'success' => true,
            'user' => $user
        ]);
    }

    public function students(Request $request)
    {
        $students = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.user_id', $request->user_id)
            ->where('products.type', 'digital_product')
            ->select('sales.*')
            ->get();

        $studentCount = Sale::join('products', 'sales.product_id', '=', 'products.id')
            ->where('sales.user_id', $request->user_id)
            ->where('products.type', 'digital_product')
            ->select('sales.*')
            ->count();

        return response()->json([
            'message' => 'Students',
            'success' => true,
            'students' => $students,
            'no of students' => $studentCount
        ]);
    }

    //I started here ///33333333333333333#######################################
    public function getAuthenticatedVendorData()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is a vendor
        if ($user->role !== 'vendor') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Return the vendor data (can customize as needed)
        return response()->json($user);
    }

    public function viewProductsByVendor()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is a vendor
        if ($user->role !== 'vendor') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fetch all products created by the authenticated vendor
        $products = Product::where('user_id', $user->id)->get();

        return response()->json($products);
    }

    public function viewProductById($id)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is a vendor
        if ($user->role !== 'vendor') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fetch the product by ID and ensure it belongs to the vendor
        $product = Product::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        // Check if the product exists
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function deleteProduct($id)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is a vendor
        if ($user->role !== 'vendor') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Find the product by ID and ensure it belongs to the vendor
        $product = Product::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        // Check if the product exists
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Delete the product
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function createDigitalProduct(DigitalProductRequest $digitalProductRequest): JsonResponse
    {
        if ($digitalProductRequest->fails()) {
            return response()->json($digitalProductRequest->errors(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $digitalProductRequest->user(); // Get authenticated user
            $productData = $digitalProductRequest->validated(); // Validate and get data
            $productData['user_id'] = $user->id; // Set the user ID for the product

            // Create the product directly in the Product model
            $digitalProduct = Product::create($productData);

            return response()->json([
                'data' => $digitalProduct,
                'message' => 'Digital Product Pending!',
            ], Response::HTTP_CREATED);
        } catch (QueryException $qe) {
            // Handle database-related exceptions
            return response()->json([
                'error' => 'Database error: ' . $qe->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $th) {
            // Handle any other exceptions
            return response()->json([
                'error' => 'An error occurred: ' . $th->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    // public function createOtherProduct(OtherProductRequest $otherProductRequest): JsonResponse
    // {
    //     try {
    //         $user = $otherProductRequest->user(); // Get the authenticated user
    //         $productData = $otherProductRequest->validated(); // Validate and get data

    //         // Handle image upload
    //         if ($otherProductRequest->hasFile('image') && $otherProductRequest->file('image')->isValid()) {
    //             $path = $otherProductRequest->file('image')->store('images/products', 'public');
    //             $productData['image'] = $path; // Store the image path
    //         } else {
    //             $productData['image'] = null; // Set image to null if no valid file
    //         }

    //         $productData['user_id'] = $user->id; // Set the user ID

    //         // Create the product directly in the Product model
    //         $otherProduct = Product::create($productData);
    //         $otherProduct['image'] = $otherProduct->image ? Storage::url($otherProduct->image) : null; // Get the URL for the image

    //         return response()->json([
    //             'data' => $otherProduct,
    //             'message' => 'Other Product Created!',
    //         ], Response::HTTP_CREATED);
    //     } catch (QueryException $qe) {
    //         // Handle database-related exceptions
    //         return response()->json([
    //             'error' => 'Database error: ' . $qe->getMessage(),
    //         ], Response::HTTP_BAD_REQUEST);
    //     } catch (\Throwable $th) {
    //         // Log the error for further debugging
    //         \Log::error('Other product creation failed', ['error' => $th->getMessage()]);
    //         return response()->json([
    //             'error' => 'An error occurred: ' . $th->getMessage(),
    //         ], Response::HTTP_BAD_REQUEST);
    //     }
    // }

    public function createOtherProduct(OtherProductRequest $otherProductRequest): JsonResponse
    {
        try {
            $user = $otherProductRequest->user(); // Get the authenticated user
            $productData = $otherProductRequest->validated(); // Validate and get data

            // Handle image uploads
            if ($otherProductRequest->hasFile('images')) {
                $images = [];
                foreach ($otherProductRequest->file('images') as $file) {
                    $images[] = $file->store('images/products', 'public');
                }

                // Set the primary image in the `image` column for compatibility
                $productData['image'] = $images[0] ?? null;

                // Store all images in the `images` column
                $productData['images'] = $images;
            }

            // Handle eBook file upload
            if ($otherProductRequest->hasFile('file') && $otherProductRequest->file('file')->isValid()) {
                $ebookPath = $otherProductRequest->file('file')->store('files/products', 'private');
                $productData['file'] = $ebookPath;
            }

            $productData['user_id'] = $user->id; // Set the user ID
            $productData['status'] = "approved";

            // Create the product
            $otherProduct = Product::create($productData);

            // Add public URLs for images and temporary URL for the eBook
            $otherProduct['image'] = $otherProduct->image ? Storage::url($otherProduct->image) : null;
            $otherProduct['images'] = array_map(fn($path) => Storage::url($path), $otherProduct->images ?? []);

            return response()->json([
                'data' => $otherProduct,
                'message' => 'Other Product Created!',
            ], Response::HTTP_CREATED);
        } catch (QueryException $qe) {
            return response()->json([
                'error' => 'Database error: ' . $qe->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $th) {
            \Log::error('Other product creation failed', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'An error occurred: ' . $th->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    public function editDigitalProduct(UpdateDigitalProductRequest $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id); // Find the product by ID
            // Check ownership
            if ($product->user_id !== auth()->id()) {
                return $this->error(null, 'You do not have permission to edit this product.', Response::HTTP_FORBIDDEN);
            }
            $productData = $request->validated(); // Get validated data

            if ($request->has('images')) {
                if (!empty($product->images) && is_array($product->images)) {
                    foreach ($product->images as $oldImage) {
                        if (Storage::disk('public')->exists($oldImage)) {
                            Storage::disk('public')->delete($oldImage); // Delete old image
                        }
                    }
                }
                // Store multiple images
                $storedImages = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images/products', 'public'); // Store in public directory
                    $storedImages[] = $path; // Add the stored path to the array
                }
                $productData['images'] = $storedImages; // Store as JSON in the database

                // Update the `image` column with the first image
                $productData['image'] = $storedImages[0] ?? null;
            }

            // Update the product fields based on the request data
            $product->update($productData);

            return $this->success($product, 'Digital Product Updated Successfully!', Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Digital product update failed', ['error' => $th->getMessage()]);
            return $this->error(null, 'Failed to update product: ' . $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // public function editOtherProduct(UpdateOtherProductRequest $request, $id): JsonResponse
    // {
    //     try {
    //         $product = Product::findOrFail($id); // Find the product by ID
    //         // Check ownership
    //         if ($product->user_id !== auth()->id()) {
    //             return $this->error(null, 'You do not have permission to edit this product.', Response::HTTP_FORBIDDEN);
    //         }
    //         $productData = $request->validated(); // Get validated data

    //         // Check if there's a new image and store it
    //         if ($request->hasFile('image') && $request->file('image')->isValid()) {
    //             $path = $request->file('image')->store('images/products', 'public');
    //             $productData['image'] = $path;
    //         } else {
    //             // If no new image, retain the existing image
    //             unset($productData['image']); // Prevent overwriting the existing image
    //         }

    //         // Update the product fields based on the request data
    //         $product->update($productData);

    //         return $this->success($product, 'Other Product Updated Successfully!', Response::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         Log::error('Other product update failed', ['error' => $th->getMessage()]);
    //         return $this->error(null, 'Failed to update product: ' . $th->getMessage(), Response::HTTP_BAD_REQUEST);
    //     }
    // }

    public function editOtherProduct(UpdateOtherProductRequest $request, $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id); // Find the product by ID

            if ($product->user_id !== auth()->id()) {
                return response()->json(['message' => 'You do not have permission to edit this product.'], Response::HTTP_FORBIDDEN);
            }

            $productData = $request->validated(); // Get validated data

            // Handle new image uploads
            if ($request->has('images')) {
                // Store multiple images
                $storedImages = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images/products', 'public'); // Store in public directory
                    $storedImages[] = $path; // Add the stored path to the array
                }
                $productData['images'] = $storedImages; // Store as JSON in the database

                // Update the `image` column with the first image
                $productData['image'] = $storedImages[0] ?? null;
            }

            // Handle eBook file upload
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $filePath = $request->file('file')->store('files/products', 'private'); // Store securely
                $productData['file_url'] = $filePath; // Save the file path
            } else {
                unset($productData['file_url']); // Don't overwrite file_url if none is provided
            }

            // Update the product fields based on the request data
            $product->update($productData);

            // Format response
            $product->images = array_map(fn($image) => '/storage/' . $image, $product->images ?? []);
            $product->image = $product->image ? '/storage/' . $product->image : null;
            $product->file_url = $product->file_url ? Storage::disk('private')->temporaryUrl($product->file_url, now()->addMinutes(60)) : null;

            return response()->json([
                'data' => $product,
                'message' => 'Other Product Updated Successfully!',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Other product update failed', ['error' => $th->getMessage()]);
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $th->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    public function destroy($id): JsonResponse
    {
        try {
            // Find the product by ID
            $product = Product::findOrFail($id);

            // Check if the authenticated user is the vendor who owns the product
            if ($product->user_id !== auth()->user()->id()) {
                return $this->error(null, 'You do not have permission to delete this product.', Response::HTTP_FORBIDDEN);
            }

            // Delete the product
            $product->delete();

            return $this->success(null, 'Product deleted successfully!', Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Product deletion failed', ['error' => $th->getMessage()]);
            return $this->error(null, 'Failed to delete product: ' . $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function withdrawal(Request $request)
    {
        try {
            $user = auth()->user();

            // Check if amount is provided
            $request->validate([
                'amount' => 'required|numeric|min:1',
            ]);

            // Use bank details from the user's profile if available
            $bankName = $user->bank_name ?? $request->input('bank_name');
            $bankAccount = $user->bank_account ?? $request->input('bank_account');

            // If bank details are missing from both the user profile and the request, return an error
            if (!$bankName || !$bankAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank name and account are required to proceed with the withdrawal request.',
                ], 400);
            }

            // Create the withdrawal request
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'amount' => $request->amount,
                'old_balance' => $user->balance, // Assuming there's a balance field in the user table
                'bank_name' => $bankName,
                'bank_account' => $bankAccount,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully.',
                'withdrawal' => $withdrawal,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the withdrawal request.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }



    public function getVendorData($id): JsonResponse
    {
        try {
            // Attempt to find the vendor by user_id
            $vendor = Vendor::where('user_id', $id)->first();

            // Check if the vendor exists
            if (!$vendor) {
                return response()->json(['message' => 'Vendor not found.'], 404);
            }

            // Return vendor details
            return response()->json([
                'message' => 'Vendor details retrieved successfully.',
                'vendor' => $vendor
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching vendor details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
