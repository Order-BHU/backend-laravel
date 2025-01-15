<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\User;
use App\Models\Category;
use App\Models\Order;
use App\Models\Cart;
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

    public function editMenu(Request $request,$menuId){
        
         if($request->user()->account_type == 'restaurant'){
            $restaurant = Restaurant::where('user_id',$request->user()->id)->first();

            if($request->user()->id != $restaurant->user_id)
            {
                return response()->json([
                    'message'=>"Your not the restaurant owner"
                ]);
            }
        
        // Find the menu by ID
        $menu = Menu::where('id',$menuId)->first();

            // Check if the menu exists
            if (!$menu) {
                return response()->json([
                    'message' => 'Menu not found.'
                ], 404);
            }

            // Validate only provided fields
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'price' => 'nullable|numeric',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg',
            ]);

            // Update only the fields that exist in the validated data
            foreach ($validatedData as $key => $value) {
                if ($key === 'image' && $request->hasFile('image')) {
                    // Handle image upload
                    $menu->image = $request->file('image')->store('image', 'public');
                } else if($key) {
                    // Update other fields
                    $menu->$key = $value;
                }
            }

        // Save the updated menu
        $menu->save();

        // Return a success response
        return response()->json([
            'message' => 'Menu updated successfully!',
            'menu' => $menu
        ]);
    }
    }

    
    public function myOrders(Request $request, $orderType){

       
        // Checks the kind of user
          if($request->user()->account_type == 'restaurant'){
            $restaurant = Restaurant::where('user_id',$request->user()->id)->first();

            // Checks if the user owns the restaurant
            if($request->user()->id != $restaurant->user_id)
            {
                return response()->json([
                    'message'=>"Your not the restaurant owner"
                ]);
            }
            
            if($orderType == 'pending'){
            $orders = Order::where('restaurant_id', $restaurant->id)
            ->where('status', 'pending')->get();

            return response()->json([
                'orders'=> $orders,
                ],200);

            } else if($orderType == 'history'){
                $orders = Order::where('restaurant_id', $restaurant->id)
                ->get();

                return response()->json([
                    'orders'=> $orders,
                ],200);
            }
            else{
                return response()->json([
                   'message'=>"Invalid order type"
                ],500);
            }
           

        }else if($request->user()->account_type == 'customer'){
            $user = User::where('id', $request->user()->id)->first();
            if ($orderType == 'pending') {
                $orders = Order::where('user_id', $user->id)
                    ->where('status', 'pending')->first();

                return response()->json([
                    'orders' => $orders,
                ], 200);

            } else if ($orderType == 'history') {
                $orders = Order::where('user_id', $user->id)
                    ->where('status', 'pending')->get();

                    $ordersArray = [];
                    foreach ($orders as $order) {
                        $restaurant = Restaurant::findByID($order->restaurant_id);

                        $ordersArray[] = [
                            'order_id' => $order->id,
                            'restaurant_name' => $restaurant->name,
                            'items' => $order->items,
                            'total' => $order->total,
                            'order_date' => $order->order_date
                        ];
                        array_push($ordersArray, $restaurant);

                    }
                return response()->json([
                    'orders' => $ordersArray,
                ], 200);
            } else {
                return response()->json([
                    'message' => "Invalid order type"
                ], 500);
            }

        }

    }
}
