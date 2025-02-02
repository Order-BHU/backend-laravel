<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Cart;
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
        $randomCode = Str::random(5);

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

        return response([
            'message' => 'Checkout Successfully',
            'code' =>$randomCode
        ]);
    }

    public function driverStatusUpdate($status)
    {
        $driver = User::where('id', auth()->user()->id)->first();
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

                return response()->json([
                    'orders' => $orders
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
            if ($orderType == 'pending') {
                $order = Order::where('user_id', $user->id)
                    ->where('status', 'pending')->first();
                $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

                return response()->json([
                    'order' => $order,
                    'restaurant_name' => $restaurant->name
                ], 200);
            } elseif ($orderType == 'history') {
                $orders = Order::where('user_id', $user->id)->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

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
                $order->status = $status;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);
            } elseif ($status == 'ready') {
                $order = Order::where('id', $orderId)->where('status', 'accepted')->first();
                $order->status = $status;
                $order->save();

                // Gets all drivers
                $drivers = User::select('id')->where('account_type', 'driver')->where('status', 'online')->get();

                // Get all drivers with no orders
                $driversWithNoOrders = array_diff($drivers->pluck('id')->toArray(), Order::pluck('driver_id')->toArray());

                // If all drivers have orders, find driver with least number of orders
                if (empty($driversWithNoOrders)) {
                    // Finds driver with least Orders
                    $availableDriver = Order::whereIn('driver_id', $drivers)
                        ->where('status', 'ready')
                        ->groupBy('driver_id')
                        ->orderByRaw('COUNT(*) ASC')
                        ->limit(1)
                        ->value('driver_id');
                    $order->driver_id = $availableDriver;
                    $order->save();
                } else {
                    $availableDriver = $driversWithNoOrders[0];
                    $order->driver_id = $availableDriver;
                    $order->save();
                }

                if (is_null($availableDriver)) {
                    // Handle case where all driver_id values are NULL
                }

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Invalid Order Status'
                ]);
            }
        } elseif ($request->user()->account_type == 'driver') {
            if ($status == 'completed') {
                $request->validate([
                    'code' => 'required'
                ]);

                $order = Order::where('id', $orderId)->where('status', 'ready')->first();
                $customer = User::where('id', $order->user_id)->first();

                if ($order) {
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
                } else {
                    return response()->json([
                        'message' => 'Order not found or not ready'
                    ], 404);
                }
            }
        }
    }
}
