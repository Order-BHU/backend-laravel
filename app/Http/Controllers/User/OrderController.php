<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function checkout(Request $request, $restaurantId)
    {
        // Validates the checkout request
        $request->validate([
            'items' => 'required|array',
            'total' => 'required|numeric',
            'location' => 'required'
        ]);

        // Generate a random 6-character alphanumeric code
        $randomCode = rand(1000, 9999);


        // Checks for Pending Order
        $pendingOrder = Order::where('user_id', $request->user()->id)
                       ->where('status', '!=', 'completed')->first();

        if(!$pendingOrder){
            // Creates a new order with the provided items, restaurant_id and user_id
            $order = Order::create([
                'user_id' => $request->user()->id,
                'items' => $request->items,
                'restaurant_id' => $restaurantId,
                'total' => $request->total,
                'customer_location' => $request->location,
                'status' => 'pending',
                'order_code' => $randomCode,
            ]);

            if ($order) {
                // Removes the cart items for the restaurant
                Cart::where('user_id', $request->user()->id)->delete();

                // Update the user's otp column with the random code
                $user = $request->user();
                $user->otp = $randomCode;
                $user->save();
            }
        }
        else {
            return response([
                'message'=>'You have a pending order, complete your order to order again'
            ],200);
        }
   
        return response([
            'message' => 'Checkout Successfully',
            'order_id' => $order->id,
            'code' =>$randomCode
        ],200);
    }

    public function driverStatusUpdate($status)
    {
        // Validate the status
        if (!in_array($status, ['online', 'offline'])) {
            return response()->json([
                'message' => 'Invalid status'
            ], 400);
        }

        $driver = User::where('id', auth()->user()->id)->first();

        if (!$driver) {
            return response()->json([
                'message' => 'Driver not found'
            ], 404);
        }

        $driver->status = $status;
        $driver->save();

        return response()->json([
            'message' => 'Driver Status Updated',
            'status' => $driver->status
        ], 200);
    }

    public function myOrders(Request $request, $orderType)
    {
        // Checks the kind of user
        if ($request->user()->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $request->user()->id)->first();

            if (!$restaurant) {
                return response()->json([
                    'message' => 'Restaurant not found'
                ], 404);
            }

            // Checks if the user owns the restaurant
            if ($request->user()->id != $restaurant->user_id) {
                return response()->json([
                    'message' => "You're not the restaurant owner"
                ]);
            }

            if ($orderType == 'pending') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                    ->where('status', 'pending')->get();

                return response()->json([
                    'orders' => $orders,
                ], 200);
            } elseif ($orderType == 'accepted') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                    ->where('status', 'accepted')->get();
                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

                    if (!$restaurant) {
                        continue;
                    }

                    $menus = [];
                    foreach($order->items as $item){
                        $menu = Menu::where('id', $item['menu_id'])->first();

                        $menuArray[]= [
                            'item_name' => $menu->name,
                            'item_price' => $menu->price,
                            'quantity' => $item['quantity']
                        ];
                        array_push($menus, $menuArray);
                        

                    }

                    $orderArray[] = [
                        'order_id' => $order->id,
                        'items' => $menus,
                        'total' => $order->total,
                        'order_date' => $order->order_date
                    ];
                    array_push($ordersArray, $orderArray);
           
                }

                return response()->json([
                    'orders' => $ordersArray,
                ], 200);
            } elseif ($orderType == 'history') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                    ->where('status', 'completed')->get();

                return response()->json([
                    'orders' => $orders,
                ], 200);
            } else {
                return response()->json([
                    'message' => "Invalid order type"
                ], 500);
            }
        } elseif ($request->user()->account_type == 'customer') {
            $user = User::where('id', $request->user()->id)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            if ($orderType == 'pending') {
                $order = Order::where('user_id', $user->id)
                    ->where('status', 'pending')->first();

                if (!$order) {
                    return response()->json([
                        'message' => 'No pending orders found'
                    ], 404);
                }

                $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

                if (!$restaurant) {
                    return response()->json([
                        'message' => 'Restaurant not found'
                    ], 404);
                }

                return response()->json([
                    'order' => $order,
                    'restaurant_name' => $restaurant->name
                ], 200);
            } elseif ($orderType == 'history') {
                $orders = Order::where('user_id', $user->id)->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

                    if (!$restaurant) {
                        continue;
                    }

                    $orderArray[] = [
                        'order_id' => $order->id,
                        'restaurant_name' => $restaurant->name,
                        'items' => $order->items,
                        'total' => $order->total,
                        'order_date' => $order->order_date
                    ];
                    array_push($ordersArray, $orderArray);
                }
                return response()->json([
                    'orders' => $ordersArray,
                ], 200);
            } else {
                return response()->json([
                    'message' => "Invalid order type"
                ], 500);
            }
        } elseif ($request->user()->account_type == 'driver') {
            if ($orderType == 'ready') {
                $orders = Order::where('driver_id', $request->user()->id)
                    ->where('status', 'ready')->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                    $user = User::where('id', $order->user_id)->first();

                    if (!$restaurant || !$user) {
                        continue;
                    }

                    $orderArray[] = [
                        'order_id' => $order->id,
                        'restaurant_name' => $restaurant->name,
                        'user_name' => $user->name,
                        'user_phoneNumber' => $user->phone_number,
                        'phone_number_type' => $user->phone_number_type,
                        'location' => $order->customer_location,
                        'items' => $order->items,
                        'total' => $order->total,
                        'order_date' => $order->order_date
                    ];
                    array_push($ordersArray, $orderArray);
                }
                return response()->json([
                    'orders' => $ordersArray,
                ], 200);

            } elseif ($orderType == 'completed' || $orderType == 'history') {
                $orders = Order::where('driver_id', $request->user()->id)
                    ->where('status', 'completed')->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                    $user = User::where('id', $order->user_id)->first();

                    if (!$restaurant || !$user) {
                        continue;
                    }

                    $orderArray[] = [
                        'order_id' => $order->id,
                        'restaurant_name' => $restaurant->name,
                        'user_name' => $user->name,
                        'user_phoneNumber' => $user->phone_number,
                        'phone_number_type' => $user->phone_number_type,
                        'location' => $order->customer_location,
                        'items' => $order->items,
                        'total' => $order->total,
                        'order_date' => $order->order_date
                    ];
                    array_push($ordersArray, $orderArray);
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

    public function updateOrderStatus(Request $request, $orderId, $status)
    {
        if ($request->user()->account_type == 'restaurant') {
            if ($status == 'accepted') {
                $order = Order::where('id', $orderId)->where('status', 'pending')->first();

                if (!$order) {
                    return response()->json([
                        'message' => 'Order not found or not in pending status'
                    ], 404);
                }

                $order->status = $status;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);
            } elseif ($status == 'ready') {
                $order = Order::where('id', $orderId)->where('status', 'accepted')->first();

                if (!$order) {
                    return response()->json([
                        'message' => 'Order not found or not in accepted status'
                    ], 404);
                }

                $order->status = $status;
                $order->save();

                // Gets all drivers
                $drivers = User::select('id')->where('account_type', 'driver')->where('status', 'online')->get();

                // Get all drivers with no orders
                $driversWithNoOrders = array_diff($drivers->pluck('id')->toArray(), Order::pluck('driver_id')->toArray());

                // If all drivers have orders, find driver with least number of orders
                if (empty($driversWithNoOrders)) {
                    // Finds driver with least Orders
                    $availableDriver = Order::select('driver_id')
                        ->whereIn('driver_id', $drivers->pluck('id')->toArray())
                        ->groupBy('driver_id')
                        ->orderByRaw('COUNT(*) ASC')
                        ->limit(1)
                        ->value('driver_id');

                } else {
                    $availableDriver = $driversWithNoOrders[0];
                }

                // Assign the order to the available driver
                $order->driver_id = $availableDriver;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully',
                    'driver_id' => $availableDriver
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Invalid Order Status'
                ], 400);
            }
        } elseif ($request->user()->account_type == 'driver') {
            if ($status == 'completed') {
                $request->validate([
                    'code' => 'required'
                ]);

                $order = Order::where('id', $orderId)->where('status', 'ready')->first();

                if (!$order) {
                    return response()->json([
                        'message' => 'Order not found or not ready'
                    ], 404);
                }

                $customer = User::where('id', $order->user_id)->first();

                if (!$customer) {
                    return response()->json([
                        'message' => 'Customer not found'
                    ], 404);
                }

                // Check if the code in the request matches the one saved in the database
                if ($request->code == $customer->otp) {
                    $order->status = $status;
                    $order->save();

                    return response()->json([
                        'message' => 'Status updated successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Invalid code'
                    ], 400);
                }
            }
        }
    }


    public function trackOrder($orderId){

        $order = Order::where('id', $orderId)->where('user_id', auth()->user()->id)->first();

        if (!$order) {
            return response()->json([
               'message' => 'Order not found'
            ],200);
        }

        $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
        if(!is_null($order->driver_id)){
            $driver = User::where('id', $order->driver_id)->first();

            return response()->json([
                'order_id' => $order->id,
                'restaurant_name' => $restaurant->name,
                'status' => $order->status,
                'driver_name' => $driver->name,
                'driver_number' => $driver->phone_number,
                'driver_profile_photo' => asset('public/storage/', $driver->profile_picture_url),
                'items' => $order->items
            ], 200);
        }
        else {
            return response()->json([
                'order_id' => $order->id,
               'restaurant_name' => $restaurant->name,
               'status' => $order->status,
                'items' => $order->items
            ], 200);
        }
      
    }

  
}
