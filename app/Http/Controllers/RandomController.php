<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Sale;
use App\Helpers\Helper;
use App\Models\Product;
use App\Models\AccessToken;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\AccessTokenMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class RandomController extends Controller
{

    public function requestAccessToken(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];

        // Check for successful transactions
        $purchases = Transaction::where('email', $email)
            ->where('status', 'success')
            ->whereNotNull('product_id')
            ->exists();

        if (!$purchases) {
            return response()->json(['message' => 'No purchases found for this email.'], 404);
        }

        // Generate a token
        $token = Str::random(40);
        $expiresAt = now()->addMinutes(15); // Token expires in 15 minutes

        // Store the token
        AccessToken::create([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        // Send the token via email
        Mail::to($email)->send(new AccessTokenMail($token));

        return response()->json(['message' => 'Access token sent to your email.']);
    }

    public function validateAccessToken(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);

        $email = $validated['email'];
        $token = $validated['token'];

        // Validate the token
        $tokenRecord = AccessToken::where('email', $email)
            ->where('token', $token)
            ->where('expires_at', '>=', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['message' => 'Invalid or expired token.'], 403);
        }

        // Fetch purchased products
        $purchases = Sale::where('email', $email)
            ->where('status', 'success')
            ->whereNotNull('product_id') // Ensure there's a product ID
            ->get();

        return response()->json([
            'products' => $purchases->map(function ($transaction) {
                // Query the product table for product details using the product_id
                $product = Product::find($transaction->product_id);

                $downloadlink = Helper::generateDownloadLink($transaction->product_id);

                if ($product) {
                    // Return product details
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'access_link' => $product->access_link,
                        'file_url' => $downloadlink, // Calling the helper function
                    ];
                }

                // Handle case where the product doesn't exist or has been deleted
                return [
                    'name' => 'Product not found',
                    'description' => 'The product associated with this transaction is no longer available.',
                    'access_link' => null,
                    'file_url' => null,
                ];
            }),
        ], 200);
    }

    public function downloadFile(Request $request)
    {
        try {
            $token = $request->input('token');
            $data = Crypt::decrypt($token);

            // Validate expiry
            if (now()->timestamp > $data['expires_at']) {
                return response()->json(['error' => 'This link has expired.'], 403);
            }

            // Find the product
            $product = Product::findOrFail($data['product_id']);

            // Check if the file exists
            if (!$product->file || !Storage::disk('private')->exists($product->file)) {
                return response()->json(['error' => 'File not found.'], 404);
            }

            // Serve the file for download
            return Storage::disk('private')->download($product->file);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid or expired token.'], 403);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'action' => 'required|string'
        ]);

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('recaptcha.v3.private_key'),
            'response' => $request->input('token'),
            'remoteip' => $request->ip()
        ]);

        $body = $response->json();

        if ($body['success'] && $body['action'] == $request->input('action') && $body['score'] >= 0.5) {
            return response()->json(['success' => true, 'score' => $body['score']]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid captcha'], 400);
    }
}
