<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function register(Request $request){

        if($request->account_type == 'customer'){

        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone_number'=>'required|numeric|unique:users',
            'phone_number_type'=>'required|in:whatsapp,sms,both',
            'account_type' => 'required|in:customer,restaurant,driver'
            ]);

        // Generate OTP
        $otp = rand(1000, 9999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone_number' => $request->phone_number,
            'phone_number_type' => $request->phone_number_type,
            'account_type'=>$request->account_type,
            'otp'=>$otp
        ]);
        
        $user->account_type = $request->account_type;
        $user->save();


        $details = array(
            "name"=>$request->name,
            "otp"=>$otp

        );

        
        $email = $request->email;

      
        Mail::send('emails.user.otp', $details, function ($message) use ($email) {
            $message->from(config("mail.from.address"), 'Order');
            $message->to($email,"Order");
            $message->subject("OTP from bhuorder");
        });

        return response()->json([
            'message'=>'OTP sent successfully, Check email'
        ],200);
    }
    else if($request->account_type == "restaurant"){


            $request->validate([
                'owners_name' => 'required|string|max:255',
                'restaurant_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'required|numeric|unique:users',
                'phone_number_type' => 'required|in:whatsapp,sms,both',
                'account_type'=>'required|in:customer,restaurant,driver'

            ]);

            // Generate OTP
            $otp = rand(1000, 9999);

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
                'name'=> $request->restaurant_name,
                'user_id'=>$user->id,

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
    }
    else if($request->account_type == "driver")
    {

    }
    else {
        return response()->json([
            'message'=>'Account type not available'
        ]);
    }


    }


    public function verifyUser(Request $request){
        $request->validate([
            'email' => 'required|string|email|max:255|exists:users',
            'otp'=> 'required'
        ]);

        $user = User::where('email',$request->email)->first();
        if($request->otp ==  $user->otp){
            $user->activated = 1;
            $user->save();

            return response()->json([
                'message'=> 'User verified!'
            ],200);
        }
        else {
            return response()->json([
                'message'=>'OTP not valid'
            ],201);
        }

    }

    public function getOtp(Request $request){
        $request->validate([
            'email' => 'required|string|email|max:255|exists:users',
        ]);


        $user = User::where("email",$request->email)->first();

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
            "message"=> "OTP sent successfully"
        ],200);


    }

    public function login(Request $request){

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
        if($user->activated != 1){        
            return response()->json([
                'message'=> 'User not yet verified, please verify user'
            ],401);
        }


        $credentials = $request->only('email', 'password');

        // Attempts to log in user
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Password not correct'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        if ($user->account_type == 'customer') {

            return response()->json([
            'message'=>"Logged in Successfully",
            'id'=>$user->id,
            'name'=> $user->name,
            'account_type'=>$user->account_type,
            'token'=>$token
        ],200)->cookie('token', $token, 60, '/', null, true, true);;
    }
    else if($user->account_type ==  'restaurant'){
        $restaurant = Restaurant::where('user_id', $user->id)->first();

            return response()->json([
                'message' => "Logged in Successfully",
                'id' => $user->id,
                'owners_name'=>$user->name,
                'restaurant_name' => $restaurant->name,
                'account_type' => $user->account_type,
                'token' => $token
            ], 200)->cookie('token', $token, 60, '/', null, true, true);
        
        
    }
    else if($user->account_type == 'driver'){

    }
    else{
            return response()->json([
                'message' => 'Account type not available'
            ]);
    }

    }

    public function logout(Request $request){

        $request->user()->currentAccessToken()->delete();

  
        return response()->json([
            'message'=>'Logged out successfully'
        ],200);
    }
}
