<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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

class AuthController extends Controller
{
    public function register(Request $request)
    {

        // Mail::raw('Test email from Laravel', function ($message) {
        //     $message->to('danieloluwasegun1000@gmail.com')->subject('Test Email');
        // });

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


            $details = array(
                "name" => $request->name,
                "otp" => $otp

            );


            $email = $request->email;

            Mail::send('emails.user.otp', $details, function ($message) use ($email) {
                $message->to($email, "Order")
                    ->subject("OTP from bhuorder");
            });


            // Mail::send('emails.user.otp', $details, function ($message) use ($email) {
            //     $message->from(config("mail.from.address"), 'Order');
            //     $message->to($email,"Order");
            //     $message->subject("OTP from bhuorder");
            // });

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

            $wallet = Wallet::create([
                'user_id' => $restaurant->id,
                'balance' => 0
            ]);


            $details = array(
                "name" => $request->name,
                "otp" => $otp
            );


            $email = $request->email;


            Mail::send('emails.user.otp', $details, function ($message) use ($email) {
                $message->from(config("mail.from.address"), 'Order');
                $message->to($email, "Order");
                $message->subject("OTP from bhuorder");
            });

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

            $user->account_type = $request->account_type;
            $user->save();


            $details = array(
                "name" => $request->name,
                "otp" => $otp

            );


            $email = $request->email;


            Mail::send('emails.user.otp', $details, function ($message) use ($email) {
                $message->from(config("mail.from.address"), 'Order');
                $message->to($email, "Order");
                $message->subject("OTP from bhuorder");
            });

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
            ], 201);
        }

    }

    public function getOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|exists:users',
        ]);


        $user = User::where("email", $request->email)->first();

        // Generate OTP
        $otp = rand(1000, 9999);

        $user->otp = $otp;
        $user->save();

        $details = array(
            "name" => $request->name,
            "otp" => $otp

        );



        $email = $request->email;


        Mail::send('emails.user.otp', $details, function ($message) use ($email) {
            $message->from(config("mail.from.address"), 'Order');
            $message->to($email);
            $message->subject("OTP from bhuorder");
        });

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
                'message' => 'User not yet verified, please verify user'
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
            $customers = User::where('account_type', 'customer')->get();
            $completedOrders = Order::where('status', 'completed')->get();

            return response()->json([
                'message' => 'Logged in Successfully',
                'admin_id' => $user->id,
                'profile_image' => asset('public/storage/', $user->profile_picture_url),
                'name' => $user->name,
                'account_type' => $user->account_type,
                'restaurants' => $restaurants,
                'customers' => $customers,
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
        if (!Auth::attempt(['email' => $user->email, 'password' => $request->current_password])) {
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
}
