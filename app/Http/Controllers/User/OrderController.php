<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Cart;
use App\Models\User;
use App\Models\Menu;

class OrderController extends Controller
{

    public function checkout(Request $request){

        // Validates the checkout request
        $request->validate([
            'items'=>'required|array',
        ]);

        // Item Array Expected Below
        // $item[] = [
        //     'menu_id'=> 23,
        //     'quantity'=>2
        // ];

        // Creates a new order with the provided items, restaurant_id and user_id
        $order = Order::create([
            'user_id' => $request->user()->id,
            'items'=>$request->items,
            'restaurant_id' => $request->restaurant_id,
            'total'=>$request->total,
            'status' => 'pending',
        ]);

        return response([
            'message'=>'Checkout Successfully'
        ]);

    }

    public function myOrders(Request $request, $orderType)
    {


        // Checks the kind of user
        if ($request->user()->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $request->user()->id)->first();

            // Checks if the user owns the restaurant
            if ($request->user()->id != $restaurant->user_id) {
                return response()->json([
                    'message' => "Your not the restaurant owner"
                ]);
            }

            if ($orderType == 'pending') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                    ->where('status', 'pending')->get();

                return response()->json([
                    'orders' => $orders,
                ], 200);

            } else if ($orderType == 'accepted') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                    ->where('status', 'accepted')->get();

                return response()->json([
                    'orders' => $orders
                ], 200);
            } else if ($orderType == 'history') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                ->where('status','completed')
                    ->get();

                return response()->json([
                    'orders' => $orders,
                ], 200);
            } else {
                return response()->json([
                    'message' => "Invalid order type"
                ], 500);
            }


        } else if ($request->user()->account_type == 'customer') {
            $user = User::where('id', $request->user()->id)->first();
            if ($orderType == 'pending') {
                $orders = Order::where('user_id', $user->id)
                    ->where('status', 'pending')->first();

                return response()->json([
                    'orders' => $orders,
                ], 200);

            } else if ($orderType == 'history') {
                $orders = Order::where('user_id', $user->id)->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::findByID($order->restaurant_id);

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

        } else if ($request->user()->account_type == 'driver') {
            $orders = Order::where('status', 'ready')->get();

            return response()->json([
                'orders' => $orders,
            ], 200);
        }

    }

    public function updateOrderStatus(Request $request, $orderId, $status)
    {

        $request->validate([
            'status' => [
                'required',
                Rule::in(Order::ALLOWED_OPTIONS),
            ],
        ]);


        if ($request->user()->account_type == 'restaurant') {
            if ($status == 'accepted') {
                $order = Order::where('id', $orderId)->where('status', 'pending')->first();
                $order->status = $status;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);

            } else if ($status == 'ready') {
                $order = Order::where('id', $orderId)->where('status', 'accepted')->first();
                $order->status = $status;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Invalid Order Status'
                ]);
            }
        } else if ($request->user()->account_type == 'driver') {
            if ($status == 'completed') {
                $order = Order::where('id', $orderId)->where('status', 'ready')->first();
                $order->status = $status;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);
            }
        }

    }
}
