<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\User\AuthController;

class ProfileController extends Controller
{
    public function myDashboard(Request $request)
    {
        return response()->json([
            'message' => $request->user()
        ]);
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
