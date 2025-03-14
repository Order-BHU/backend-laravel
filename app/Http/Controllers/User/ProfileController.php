<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\User\AuthController;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Transactions;

class ProfileController extends Controller
{
    public function myDashboard(Request $request)
    {
        $user = $request->user();
        $user->profile_picture_url = asset('public/storage/' . $user->profile_picture_url);

        $baseResponse = [
            'user' => $user,
            'account_type' => $user->account_type
        ];

        if ($user->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $user->id)->first();
            $wallet = Wallet::where('user_id', $restaurant->id)->first();
            $pendingOrders = Order::where('restaurant_id', $restaurant->id)
                ->where('status', 'pending')
                ->count();
            $completedOrders = Order::where('restaurant_id', $restaurant->id)
                ->where('status', 'completed')
                ->count();
            $acceptedOrders = Order::where('restaurant_id', $restaurant->id)
                ->where('status', 'accepted')
                ->count();

   
            $transactions = Transactions::where('restaurant_id', $restaurant->id)
                ->orderBy('created_at', 'desc')
                ->get(['amount', 'type', 'status', 'reference', 'created_at']);


            return response()->json(array_merge($baseResponse, [
                'restaurant_details' => [
                    'name' => $restaurant->name,
                    'logo' => $user->profile_picture_url,
                 
                ],
                'wallet_balance' => $wallet->balance,
                'statistics' => [
                    'pending_orders' => $pendingOrders,
                    'completed_orders' => $completedOrders,
                    'accepted_orders' => $acceptedOrders,
                    'transactions' => $transactions
                ]
            ]));
        }

        if ($user->account_type == 'customer') {
            $pendingOrder = Order::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();
            $orderHistory = Order::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count();
            $transactions = Transactions::where('customer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get(['amount', 'type', 'status', 'reference', 'created_at']);


            return response()->json(array_merge($baseResponse, [
                'completed_orders' => $orderHistory,
                'transactions'=> $transactions
            ]));
        }

        if ($user->account_type == 'driver') {
            $acceptedDeliveries = Order::where('driver_id', $user->id)
                ->where('status', 'accepted')
                ->count();
            $deliveringDeliveries = Order::where('driver_id', $user->id)
                ->where('status', 'delivering')
                ->count();
            $completedDeliveries = Order::where('driver_id', $user->id)
                ->where('status', 'completed')
                ->count();

            return response()->json(array_merge($baseResponse, [
                'status' => $user->status,
                'accepted_deliveries' => $acceptedDeliveries,
                'delivering_deliveries' => $deliveringDeliveries,
                'total_deliveries' => $completedDeliveries
            ]));
        }

        return response()->json($baseResponse);
    }

    public function getLocations()
    {
        $locations = Location::all();

        return response()->json([
            'locations' => $locations
        ]);
    }

    public function updateProfilePicture(Request $request)
    {
        // Validate the request
        $picture = $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // Handle image upload
        $imagePath = $request->file('profile_picture')->store('image', 'public');

        // Update the user profile picture
        $user = auth()->user();
        $user->profile_picture_url = $imagePath;
        $user->save();

        if ($user->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $user->id)->first();
            $restaurant->logo = $imagePath;
            $restaurant->save();
        }

        return response()->json([
            'message' => "Profile Picture Updated successfully"
        ]);
    }

    public function editProfile(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'image|mimes:jpeg,png,jpg,gif,svg',
            'name' => 'string|max:255',
            'phone_number_type' => 'string|max:255',
            'phone_number' => 'string|max:12',
            // 'email' => 'string|email|max:255|unique:users,email,' . auth()->id(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        // Handle image upload if present
        if ($request->hasFile('profile_picture')) {
            $imagePath = $request->file('profile_picture')->store('image', 'public');
            $user->profile_picture_url = $imagePath;

            if ($user->account_type == 'restaurant') {
                $restaurant = Restaurant::where('user_id', $user->id)->first();
                $restaurant->logo = $imagePath;
                $restaurant->save();
            }
        }

        // Update other profile details if present
        if ($request->name) {
            $user->name = $request->name;
        }

        if ($request->phone_number_type) {
            $user->phone_number_type = $request->phone_number_type;
        }
        if ($request->phone_number) {
            $user->phone_number = $request->phone_number;
        }

        // // Check if email is updated
        // if ($request->email && $user->email !== $request->email) {
        //     $user->email = $request->email;
        //     $user->email_verified_at = null; // Reset email verification
        //     $authController = new AuthController();
        //     $authController->getOtp($request); // Call getOtp function
        // }

        $user->save();

        return response()->json([
            'message' => "Profile Updated successfully",
            'user' => $user
        ]);
    }
}
