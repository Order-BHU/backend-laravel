<?php

namespace App\Http\Controllers\User;

use App\Models\DriverTransfers;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Transactions;
use App\Models\Wallet;
use App\Models\Driver;

class OrderController extends Controller
{

    public function initializeCheckout(Request $request, $restaurantId)
    {
        $request->validate([
            'total' => 'required|numeric',
            'callback_id' => 'required',
            'items' => 'required|array',
            'location' => 'required',
        ]);


        $order = Order::where('user_id', auth()->user()->id)->where('status', '!=', 'completed')->first();

        if ($order) {
            return response()->json([
                'message' => 'You have a pending order, complete your order to order again'
            ])->setStatusCode(400);
        }
    
       
        $user = $request->user();
        $fee =  300 * 100;
        $total = ($request->total * 100) + $fee;
        $restaurant = Restaurant::where('id', $restaurantId)->first();
        // $response = Http::withHeaders([
        //     'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        //     'Content-Type' => 'application/json',
        // ])->post('https://api.paystack.co/transaction/initialize', [
        //             'email' => $user->email,
        //             'amount' => $total, // Amount in kobo
        //             // 'subaccount' => $restaurant->subaccount_code, 
        //             // 'transaction_charge' => $fee,   
        //             'callback_url' => 'https://bhuorder.com/menu/' . $request->callback_id,
        //             'metadata' => [
        //                 'user_id' => $user->id,
        //                 'restaurant_id' => $restaurantId,
        //                 'total' => $request->total,
        //                 'items' => $request->items,
        //                 'location' => $request->location,

                   
        //                 // anything else you want...
        //             ]

        //         ]);

        $payload = [
            'email' => $user->email,
            'amount' => $total, // Amount in kobo
            'callback_url' => 'https://bhuorder.com/menu/' . $request->callback_id,
            'metadata' => [
                'user_id' => $user->id,
                'restaurant_id' => $restaurantId,
                'total' => $request->total,
                'items' => $request->items,
                'location' => $request->location,
            ]
        ];

        if ($restaurant->id != 14) {
            $payload['subaccount'] = $restaurant->subaccount_code;
            $payload['transaction_charge'] = $fee; // optional if you're taking a commission
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', $payload);


        $data = $response->json();
        if (!$data['status']) {
            return response()->json(
                [$response->json(),$total,$fee], 400);
        }

        return response()->json(['data' => $data['data']], 200);

    }
    public function checkout(Request $request, $restaurantId, BrevoMailer $brevo)
    {
        // Validates the checkout request
        $request->validate([
            'reference' => 'required'
        ]);

        $user = $request->user();

        // Checks for Pending Order
        $pendingOrder = Order::where('user_id', $request->user()->id)
            ->where('status', '!=', 'completed')->first();

        if (!$pendingOrder) {


            $restaurant = Restaurant::where('id', $restaurantId)->first();


            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            ])->get("https://api.paystack.co/transaction/verify/" . $request->reference);

            $data = $response->json();

            // Checks if the payment was successful
            if ($data['status'] && $data['data']['status'] === 'success') {
                return response([
                    'message' => 'Payment Successful',
                    'data' => $data
                ], 200);


            } else {
                return response([
                    'message' => 'Payment Failed',
                    'data' => $data
                ], 400);
            }

        } else {
            return response([
                'message' => 'You have a pending order, complete your order to order again'
            ], 200);
        }
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
                    ->where('status', 'pending')->with('user')->get();

                return response()->json([
                    'orders' => $orders,
                ], 200);
            } elseif ($orderType == 'accepted') {
                $orders = Order::where('restaurant_id', $restaurant->id)
                    ->where('status', 'accepted')->orderBy('created_at', 'desc')->get();
                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

                    if (!$restaurant) {
                        continue;
                    }

                    $menus = [];
                    foreach ($order->items as $item) {
                        $menu = Menu::where('id', $item['menu_id'])->first();

                        $menuArray = [
                            'menu_name' => $menu->name,
                            'item_price' => $menu->price,
                            'quantity' => $item['quantity']
                        ];
                        array_push($menus, $menuArray);
                    }

                    $orderArray = [
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'items' => $menus,
                        'total' => $order->total,
                        'location'=>$order->customer_location,
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

                    $orderArray = [
                        'order_id' => $order->id,
                        'restaurant_name' => $restaurant->name,
                        'items' => $order->items,
                        'total' => $order->total,
                        'location' => $order->customer_location,
                        'order_date' => $order->order_date,
                        'status' => $order->status
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
                    ->where('status', 'ready')->orderBy('created_at', 'desc')->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                    $user = User::where('id', $order->user_id)->first();

                    if (!$restaurant || !$user) {
                        continue;
                    }

                    $orderArray = [
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

            } elseif ($orderType == 'delivering') {
                $orders = Order::where('driver_id', $request->user()->id)
                    ->where('status', 'delivering')->get();

                $ordersArray = [];
                foreach ($orders as $order) {
                    $restaurant = Restaurant::where('id', $order->restaurant_id)->first();
                    $user = User::where('id', $order->user_id)->first();

                    if (!$restaurant || !$user) {
                        continue;
                    }

                    $orderArray = [
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

                    $orderArray = [
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

    public function updateOrderStatus(Request $request, $orderId, $status, BrevoMailer $brevo)
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


                // Gets all drivers
                $drivers = User::select('id')->where('account_type', 'driver')->where('status', 'online')->get();
                $driverIds = $drivers->pluck('id')->toArray();

                // Get all driver IDs with active (non-completed) orders
                $busyDrivers = Order::where("status", "!=", "completed")->pluck('driver_id')->toArray();

                // Get drivers with no active orders
                $driversWithNoOrders = array_diff($driverIds, $busyDrivers);

                if (empty($driversWithNoOrders)) {
                    // Finds driver with least orders
                    $availableDriver = Order::select('driver_id')
                        ->whereIn('driver_id', $driverIds)
                        ->groupBy('driver_id')
                        ->orderByRaw('COUNT(*) ASC')
                        ->limit(1)
                        ->value('driver_id');

                    if (!$availableDriver) {
                        return response()->json([
                            'message' => 'No available driver at the moment',
                        ], 200);
                    }

                } else {
                    $availableDriver = array_values($driversWithNoOrders)[0]; // To get indexed value
                }


                $order->status = $status;
                $order->save();

                // Assign the order to the available driver
                $order->driver_id = $availableDriver;
                $order->save();

                $driverDetails = User::find($availableDriver);
                $customerDetails = User::find($order->user_id);
                $restaurantDetails = Restaurant::find($order->restaurant_id);

                $details = [
                    'order_id'=> $order->id,
                    'order_date'=> $order->created_at->format('Y-m-d H:i:s'),
                    'orderItems' => $order->items,
                    'customer_name' => $customerDetails->name,
                    'customer_phone' => $customerDetails->phone_number,
                    'customer_email' => $customerDetails->email,
                    'pickup_location' => $restaurantDetails->name,
                    'delivery_address' => $order->customer_location,


                ];

                $htmlContent = view('emails.user.order', $details)->render();



                $email = $driverDetails->email;

                // Send notification
                $brevo->sendMail(
                    $email,
                    $driverDetails->name,
                    'You Have An Order '. $restaurantDetails->name,
                    $htmlContent,
                    config("mail.from.address", "support@bhuorder.com"),  // from email
                    'Order'             // from name
                );




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

            if ($status == 'delivering') {
                $order = Order::where('id', $orderId)->where('driver_id', $request->user()->id)->first();

                if (!$order) {
                    return response()->json([
                        'message' => 'Order not found or not assigned to this driver'
                    ], 404);
                }

                $order->status = $status;
                $order->save();

                return response()->json([
                    'message' => 'Status updated successfully'
                ], 200);

            } else if ($status == 'completed') {
                $request->validate([
                    'code' => 'required'
                ]);

                $order = Order::where('id', $orderId)->first();

                if (!$order) {
                    return response()->json([
                        'message' => 'Order not found or not ready'
                    ], 404);
                }

                $driver = Driver::where('user_id',$order->driver_id)->first();



                // Check if the code in the request matches the one saved in the database
                if ($request->code == $order->code) {

                   
                    $reference = Str::uuid();

                    if(!$driver){
                        return response()->json(
                            [
                                'message'=>'Your account details is not registered'
                            ],
                            401);
                    }

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                        'Content-Type' => 'application/json',
                    ])->post(env('PAYSTACK_PAYMENT_URL') . '/transfer', [
                                "source" => "balance",
                                "amount" => 190*100, // Amount in kobo
                                "reference" => $reference,
                                "recipient" => $driver->recipient_code,
                                "reason" => "Delivery Completed"
                            ]);

                    $data = $response->json();
                    if (!$data['status']) {
                        return response()->json(['error' => $data['message']], 400);
                    }

                    $driverTransfer = DriverTransfers::create([
                        'user_id'=>$order->driver_id,
                        'status'=> 'pending',
                        'reference'=> $reference,
                    ]);


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


    public function trackOrder()
    {
        $user = auth()->user();
        
        // Get the active order with eager loading to reduce queries
        $order = Order::where('user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'No Active Order'
            ], 200);
        }

        // Get restaurant owner information
        $restaurantOwner = Restaurant::where('id', $order->restaurant_id)->first();

        // Calculate estimated delivery time
        $estimatedDeliveryTime = null;
        if ($order->status === 'delivering' && $order->driver_id) {
            // Get average delivery time for this driver
            $avgDeliveryTime = Order::where('driver_id', $order->driver_id)
                ->where('status', 'completed')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time')
                ->first();
            
            if ($avgDeliveryTime->avg_time) {
                $estimatedDeliveryTime = now()->addMinutes(round($avgDeliveryTime->avg_time));
            }
        }

        // Get order items with menu details
        $items = collect($order->items)->map(function ($item) {
            $menu = Menu::find($item['menu_id']);
            return [
                'menu_name' => $menu ? $menu->name : 'Unknown Item',
                'quantity' => $item['quantity'],
                'menu_price' => $menu ? $menu->price : 0,
                'total' => $menu ? ($menu->price * $item['quantity']) : 0
            ];
        });

        // Prepare response data
        $response = [
            'total' => $order->total,
            'order_id' => $order->id,
            'restaurant_name' => $restaurantOwner->name,
            'restaurant_email' => $restaurantOwner->email,
            'order_code' => $order->code,
            'status' => $order->status,
            'items' => $items,
            'order_date' => $order->created_at->format('Y-m-d H:i:s'),
            'customer_location' => $order->customer_location,
            'estimated_delivery_time' => $estimatedDeliveryTime ? $estimatedDeliveryTime->format('Y-m-d H:i:s') : null,
            'time_elapsed' => $order->created_at->diffForHumans(),
            'status_history' => [
                'pending' => $order->created_at->format('Y-m-d H:i:s'),
                'accepted' => $order->status === 'accepted' ? $order->updated_at->format('Y-m-d H:i:s') : null,
                'ready' => $order->status === 'ready' ? $order->updated_at->format('Y-m-d H:i:s') : null,
                'delivering' => $order->status === 'delivering' ? $order->updated_at->format('Y-m-d H:i:s') : null
            ]
        ];

        $driver = User::where('id', $order->driver_id)->first();

        // Add driver information if available
        if ($driver) {
            $response['driver_name'] = $driver->name;
            $response['driver_number'] = $driver->phone_number;
            $response['driver_profile_photo'] = asset('public/storage/' . $driver->profile_picture_url);
            
            // Add driver's current status
            $response['driver_status'] = $driver->status;
            
        
        }

        return response()->json($response, 200);
    }


    public function allOrders(Request $request)
    {
        if ($request->user()->account_type != 'admin') {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }

        $orders = Order::with(['user', 'driver', 'restaurant'])
        ->orderBy('created_at', 'desc')
        ->get();


        return response()->json([
            'orders' => $orders,
        ], 200);
    }

    public function driverList($status){
        $drivers = User::where('account_type', 'driver')
            ->select('id', 'name', 'phone_number', 'profile_picture_url', 'status')
            ->where('status', $status)
            ->get()
            ->map(function ($driver) {
                $driver->profile_picture_url = asset('public/storage/' . $driver->profile_picture_url);
                return $driver;
            });
        $alldrivers = User::where('account_type', 'driver')
            ->select('id', 'name', 'phone_number', 'profile_picture_url', 'status')
            ->get()
            ->map(function ($driver) {
                $driver->profile_picture_url = asset('public/storage/' . $driver->profile_picture_url);
                return $driver;
            });

        return response()->json([
            $status . 'drivers' => $drivers,
            'all_drivers' => $alldrivers

        ], 200);
    }

  public function updateOrder(Request $request, $orderId) {

        if ($request->user()->account_type != 'admin') {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }

        $order = Order::where('id', $orderId)->first();

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    if($request->status) {
        $status = $request->input('status');
        $order->status = $status;
    }
    if($request->driver_id) {
        $driverId = $request->input('driver_id');
        $order->driver_id = $driverId;
    }

    $order->save();

    return response()->json([
        'message' => 'Order updated successfully',
        'order' => $order
    ], 200);

}

    public function adminDriverStatusUpdate($status, $driverId)
    {
        // Checks if the user is an admin
        if (auth()->user()->account_type != 'admin') {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }
        // Validate the status
        if (!in_array($status, ['online', 'offline'])) {
            return response()->json([
                'message' => 'Invalid status'
            ], 400);
        }
        $driver = User::where('id', $driverId)->first();

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


}
