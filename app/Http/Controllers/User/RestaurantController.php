<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\User;
use App\Models\Category;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function restaurantList(){
        $allRestaurants = Restaurant::select('id','name','cover_picture')->get();

        return response()->json([
            'restaurant_list'=>$allRestaurants
        ]);
    }

    public function categories(){
        $categories = Category::select('id','name')->get();

        return response()->json([
            'categories'=> $categories
        ]);
    }


    public function menuList($restaurantId){

      

        $categories = Category::select('name','id')->with('menus' )->get();



        return response()->json([
            'menu'=> $categories,
        ],200);
    }

    public function addMenu(Request $request, $restaurantId){
        if($request->user()->account_type == 'restaurant'){
            $restaurant = Restaurant::where('user_id',$request->user()->id)->first();

            if($request->user()->id != $restaurant->user_id)
            {
                return response()->json([
                    'message'=>"Your not the restaurant owner"
                ]);
            }

        $request->validate([
            'name'=> 'required|string',
            'description'=>'required|string',
            'category_id'=>'required',
            'price'=>'required',
            'image'=>'required|image|mimes:jpg,png,jpeg,gif,svg'
        ]);

            // Handle image upload
            $imagePath = $request->file('image')->store('image', 'public');

            // Create the menu
            $menu = Menu::create([
                'name' => $request->name,
                'description' => $request->description,
                'restaurant_id' => $restaurantId,
                'price'=>$request->price,
                'category_id' => $request->category_id,
                'image' => $imagePath
            ]);

            return response()->json([
                'message' => 'Menu created successfully!',
                'menu' => $menu
            ], 201);
    }
    else {
        return response()->json([
            'message'=>'User has no acess'
        ],500);
    }


    }
}
