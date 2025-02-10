<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function myDashboard(Request $request){
        return response()->json([
            'message'=> $request->user()
        ]);
    }

    public function getLocations(){

        $locations = Location::all();

        return response()->json([
            'locations'=> $locations
        ]);
    }

    public function updateProfilePicture(Request $request){

        // Validate the request
        $picture = $request->validate([
            'profile_picture'=>'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // Handle image upload
        $imagePath = $request->file('profile_picture')->store('image', 'public');

        // Update the user profile picture
        $user = auth()->user();
        $user->profile_picture_url = $imagePath;
        $user->save();

        if($user->account_type == 'restaurant'){
        $restaurant = Restaurant::where('user_id', $user->id)->first();
        $restaurant->logo = $imagePath;
        $restaurant->save();
        }

        return response()->json([
            'message'=>"Profile Picture Updated successfully"
        ]);



    }
}
