<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function restaurantList(){
        $allRestaurants = Restaurant::all();

        return response()->json([
            'restaurant_list'=>$allRestaurants
        ]);
    }
}
