<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use App\Services\BrevoMailer;
use Exception;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * @var  BrevoMailer $brevo - BrevoMailer instance
     */
    protected $brevo;

    public function register(Request $request, BrevoMailer $brevo)
    {
        if ($request->account_type == 'customer') {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'required|numeric|unique:users',
                'phone_number_type' => 'required|in:whatsapp,sms,both',
                'account_type' => 'required|in:customer,driver'
            ]);

            // Generate OTP
            $otp = rand(1000, 9999);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'phone_number' => $request->phone_number,
                'phone_number_type' => $request->phone_number_type,
                'account_type' => $request->account_type,
                'otp' => $otp
            ]);

            $user->account_type = $request->account_type;
            $user->save();


            $details = [
                "name" => $request->name,
                "otp" => $otp

            ];


            $htmlContent = view('emails.user.otp', $details)->render();

            $brevo->sendMail(
                $user->email,
                $user->name,
                "OTP Request",
                $htmlContent,
                config("mail.from.address", "support@bhuorder.com.ng"),  // from email
                'Onboarding Team'             // from name
            );



            return response()->json([
                'message' => 'OTP sent successfully, Check email'
            ], 200);
        } else if ($request->account_type == "restaurant") {


            $request->validate([
                'owners_name' => 'required|string|max:255',
                'restaurant_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'required|numeric|unique:users',
                'phone_number_type' => 'required|in:whatsapp,sms,both',
                'account_no' => 'required|string',
                'bank_name' => 'required|string',
                'bank_code' => 'required|string',
                'account_type' => 'required'
            ]);

            // Generate OTP
            $otp = rand(1000, 9999);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/subaccount', [
                        'business_name' => $request->restaurant_name,
                        'bank_code' => $request->bank_code,
                        'account_number' => $request->account_no,
                        'percentage_charge' => 10,
                    ]);


            $data = $response->json();
            if (!$data['status']) {
                return response()->json(['error' => $data['message']], 400);
            }


            $user = User::create([
                'name' => $request->owners_name,
                'email' => $request->email,
                'password' => $request->password,
                'phone_number' => $request->phone_number,
                'phone_number_type' => $request->phone_number_type,
                'otp' => $otp,
            ]);
            $user->account_type = $request->account_type;
            $user->save();

            $restaurant = Restaurant::create([
                'name' => $request->restaurant_name,
                'user_id' => $user->id,
                'account_no' => $request->account_no,
                'bank_name' => $request->bank_name,
                'bank_code' => $request->bank_code,
                'subaccount_code' => $data['data']['subaccount_code'],

            ]);

            $resWallet = Wallet::create([
                'user_id' => $restaurant->id,
                'balance' => 0
            ]);


            $details = [
                "name" => $request->name,
                "otp" => $otp

            ];


            $htmlContent = view('emails.user.otp', $details)->render();

            $brevo->sendMail(
                $user->email,
                $user->name,
                "OTP Request",
                $htmlContent,
                config("mail.from.address", "support@bhuorder.com.ng"),  // from email
                'Onboarding Team'             // from name
            );

            return response()->json([
                'message' => 'OTP sent successfully, Check email'
            ], 200);
        } else if ($request->account_type == "driver") {

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'required|numeric|unique:users',
                'phone_number_type' => 'required|in:whatsapp,sms,both',
                'account_no' => 'required|string',
                'bank_code' => 'required|string',
                'account_type' => 'required'
            ]);

            // Generate OTP
            $otp = rand(1000, 9999);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'phone_number' => $request->phone_number,
                'phone_number_type' => $request->phone_number_type,
                'status' => 'online', //Driver is online by default
                'account_type' => $request->account_type,
                'otp' => $otp
            ]);

            $driWallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('PAYSTACK_PAYMENT_URL') . '/transferrecipient', [
                        "type" => "nuban",
                        "name" => $user->name,
                        "account_number" => $request->account_no,
                        "bank_code" => $request->bank_code
                    ]);

                    


            $data = $response->json();
            if (!$data['status']) {
                return response()->json(['error' => $data['message']], 400);
            }


            $driver = Driver::create([
                'user_id'=> $user->id,
                'recipient_code'=> $data['data']['recipient_code']
            ]);



            $details = [
                "name" => $request->name,
                "otp" => $otp
            ];


            $htmlContent = view('emails.user.otp', $details)->render();

            $brevo->sendMail(
                $user->email,
                $user->name,
                "OTP Request",
                $htmlContent,
                config("mail.from.address", "support@bhuorder.com.ng"),  // from email
                'Onboarding Team'             // from name
            );

            return response()->json([
                'message' => 'OTP sent successfully, Check email'
            ], 200);

        } else {
            return response()->json([
                'message' => 'Account type not available'
            ]);
        }


    }


    public function verifyUser(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|exists:users',
            'otp' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if ($request->otp == $user->otp) {
            $user->activated = 1;
            $user->save();

            return response()->json([
                'message' => 'User verified!'
            ], 200);
        } else {
            return response()->json([
                'message' => 'OTP not valid'
            ], 400);
        }

    }

    public function getOtp(Request $request, BrevoMailer $brevo)
    {
        // Get authenticated user
        $user = $request->user();
        // Generate OTP
        $otp = rand(1000, 9999);

        $user->otp = $otp;
        $user->save();

        $details = [
            "name" => $user->name,
            "otp" => $otp

        ];

        $htmlContent = view('emails.user.otp', $details)->render();

        
        $email = $user->email;

        $brevo->sendMail(
            $email,
            $user->name,
            'OTP from bhuorder',
            $htmlContent,
            config("mail.from.address", "support@bhuorder.com"),  // from email
            'Onboarding Team'             // from name
        );



        return response()->json([
            "message" => "OTP sent successfully"
        ], 200);


    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email|max:255|exists:users',
            'password' => 'required|min:8|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not registered, Please Login',
            ], 401);
        }
        
        // User not yet verified
        if ($user->activated != 1) {
            return response()->json([
                'message' => 'User not yet verified, please verify user',
                'token' => $user->createToken('auth_token')->plainTextToken
            ], 401);
        }


        $credentials = $request->only('email', 'password');

        // Attempts to log in user
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Password not correct'], 401);
        }

        $user = $request->user();

        // Delete existing tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->account_type == 'customer') {

            return response()->json([
                'message' => "Logged in Successfully",
                'id' => $user->id,
                'name' => $user->name,
                'profile_image' => asset('public/storage', $user->profile_picture_url),
                'account_type' => $user->account_type,
                'token' => $token
            ], 200)->cookie('token', $token, 60, '/', null, true, true);
        } else if ($user->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $user->id)->first();

            return response()->json([
                'message' => "Logged in Successfully",
                'id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'profile_image' => asset('public/storage/', $user->profile_picture_url),
                'owners_name' => $user->name,
                'restaurant_name' => $restaurant->name,
                'account_type' => $user->account_type,
                'token' => $token
            ], 200)->cookie('token', $token, 60, '/', null, true, true);


        } else if ($user->account_type == 'driver') {
            return response()->json([
                'message' => "Logged in Successfully",
                'id' => $user->id,
                'name' => $user->name,
                'profile_image' => asset('public/storage', $user->profile_picture_url),
                'account_type' => $user->account_type,
                'status' => $user->status,
                'token' => $token
            ], 200)->cookie('token', $token, 60, '/', null, true, true);
        } else if ($user->account_type == 'admin') {
            $restaurants = Restaurant::all();
            $completedOrders = Order::where('status', 'completed')->get();

            return response()->json([
                'message' => 'Logged in Successfully',
                'admin_id' => $user->id,
                'profile_image' => asset('public/storage/', $user->profile_picture_url),
                'name' => $user->name,
                'account_type' => $user->account_type,
                'restaurants' => $restaurants,
                'token' => $token
            ], 200)->cookie('token', $token, 60, '/', null, true, true);

        } else {
            return response()->json([
                'message' => 'Account type not available'
            ], 400);
        }

    }

    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();


        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|different:current_password',
            'confirm_password' => 'required|string|min:8|same:new_password'
        ]);

        $user = $request->user();


        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->password = $request->new_password;
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'email' => $googleUser->getEmail(),
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'profile_picture_url' => $googleUser->getAvatar(),
                    'account_type' => 'customer',
                    'password' => Hash::make(Str::random(16)),
                    'activated' => 1
                ]);
            }

            // Ensure 'auth_token' matches the name used in createToken
            $token = $user->createToken('auth_token')->plainTextToken;

            // Prepare data to send back to the React app
            $data = [
                'status' => 'success',
                'message' => $user->wasRecentlyCreated ? 'User created successfully' : 'Login successful',
                'token' => $token,
                'user' => [ // Send only necessary user info
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_picture_url' => $user->profile_picture_url,
                    'account_type' => $user->account_type, 
                ]
            ];

            // Return HTML with JavaScript to post message and close popup
            return view('auth.callback', ['data' => $data]); // Pass data as JSON string

        } catch (Exception $e) {
            // Log the error for debugging

            // Return HTML with error message for the popup
            $errorData = [
                'status' => 'error',
                'message' => 'Authentication failed. Please try again.',
                'error' => $e->getMessage() // Optionally include error details in development
            ];
            return view('auth.callback', ['data' => $errorData]);
        }
    }

    public function forgotPassword(Request $request, BrevoMailer $brevo)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|exists:users',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a random token
        $token = Str::random(64);

        // Store the token in the password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );

        // Generate the reset URL
        $resetUrl = env('FRONTEND_URL', 'https://bhuorder.com') . '/reset-password?token=' . $token;

        $details = [
            'name' => $user->name,
            'resetUrl' => $resetUrl
        ];

        $htmlContent = view('emails.user.password-reset', $details)->render();

        $brevo->sendMail(
            $user->email,
            $user->name,
            'Password Reset Request',
            $htmlContent,
            config("mail.from.address", "support@bhuorder.com.ng"),
            'Order Support'
        );

        return response()->json([
            'message' => 'Password reset link sent to your email'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            DB::table('password_reset_tokens')->where('token', $request->token)->delete();
            return response()->json([
                'message' => 'Reset token has expired'
            ], 400);
        }

        $user = User::where('email', $passwordReset->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the used token
        DB::table('password_reset_tokens')->where('token', $request->token)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully'
        ], 200);
    }
}
