<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\User\AuthController;
use App\Models\Order;
use App\Models\User;
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
                    'id' => $restaurant->id,
                    'email' => $restaurant->email,
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
                'transactions' => $transactions
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

        if ($user->account_type == 'admin') {
            // Basic statistics
            $totalOrders = Order::count();
            $totalRestaurants = Restaurant::count();
            $totalCustomers = User::where('account_type', 'customer')->count();
            $totalDrivers = User::where('account_type', 'driver')->count();

            // Enhanced order statistics
            $pendingOrders = Order::where('status', 'pending')->count();
            $acceptedOrders = Order::where('status', 'accepted')->count();
            $deliveringOrders = Order::where('status', 'delivering')->count();
            $completedOrders = Order::where('status', 'completed')->count();

            // User activity metrics
            $activeDrivers = User::where('account_type', 'driver')
                ->where('status', 'online')
                ->count();
            $inactiveDrivers = User::where('account_type', 'driver')
                ->where('status', 'offline')
                ->count();

            // Recent transactions
            $recentTransactions = Transactions::orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Restaurant performance metrics
            $restaurantMetrics = Restaurant::with([
                'orders' => function ($query) {
                    $query->select('restaurant_id')
                        ->selectRaw('COUNT(DISTINCT id) as total_orders')
                        ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
                        ->where('status', 'completed')
                        ->groupBy('restaurant_id');
                }
            ])
                ->withCount([
                    'orders as pending_count' => function ($query) {
                        $query->where('status', 'pending');
                    }
                ])
                ->withCount([
                    'orders as completed_count' => function ($query) {
                        $query->where('status', 'completed');
                    }
                ])
                ->get();

            // Active user sessions
            $activeSessions = User::whereNotNull('remember_token')
                ->where('updated_at', '>=', now()->subHours(24))
                ->count();

            // System overview
            return response()->json(array_merge($baseResponse, [
                // User statistics
                'total_orders' => $totalOrders,
                'total_restaurants' => $totalRestaurants,
                'total_customers' => $totalCustomers,
                'total_drivers' => $totalDrivers,
                'active_drivers' => $activeDrivers,
                'inactive_drivers' => $inactiveDrivers,
                'active_sessions' => $activeSessions,

                // Order statistics
                'order_metrics' => [
                    'pending' => $pendingOrders,
                    'accepted' => $acceptedOrders,
                    'delivering' => $deliveringOrders,
                    'completed' => $completedOrders
                ],

                // Financial overview
                'transactions' => [
                    'recent' => $recentTransactions,
                    'total_revenue' => Transactions::where('status', 'completed')
                        ->sum('amount')
                ],

                // Restaurant performance
                'restaurant_metrics' => $restaurantMetrics->map(function ($restaurant) {
                    return [
                        'id' => $restaurant->id,
                        'name' => $restaurant->name,
                        'total_orders' => $restaurant->completed_count,
                        'pending_orders' => $restaurant->pending_count,
                        'total_revenue' => $restaurant->orders->first()->total_revenue ?? 0,
                        'wallet_balance' => Wallet::where('user_id', $restaurant->id)
                            ->value('balance') ?? 0,
                        'average_order_value' => $restaurant->completed_count > 0
                            ? ($restaurant->orders->first()->total_revenue / $restaurant->completed_count)
                            : 0
                    ];
                }),

                // System health
                'system_status' => [
                    'api_version' => '1.0',
                    'last_backup' => now(),
                    'server_time' => now()
                ]
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

    public function updateCoverPicture(Request $request)
    {
        // Validate the request
        $request->validate([
            'cover_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $user = $request->user();

        if ($user->account_type !== 'restaurant') {
            return response()->json([
                'message' => 'Only restaurants can update cover pictures'
            ], 403);
        }

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            return response()->json([
                'message' => 'Restaurant not found'
            ], 404);
        }

        // Store the image
        if ($request->hasFile('cover_picture')) {
            $image = $request->file('cover_picture');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->store('image', $filename);
            
            // Update the restaurant's cover picture
            $restaurant->cover_picture = $image;
            $restaurant->save();
        }

        return response()->json([
            'message' => 'Cover picture updated successfully',
            'cover_picture_url' => asset('public/storage/' . $restaurant->cover_picture)
        ]);
    }

    public function editProfile(Request $request)
    {
        $user = $request->user();

        // Validate the request
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|numeric|unique:users,phone_number,' . $user->id,
            'phone_number_type' => 'nullable|in:whatsapp,sms,both',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'cover_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // Update user profile
        if ($request->name) {
            $user->name = $request->name;
        }
        if ($request->phone_number_type) {
            $user->phone_number_type = $request->phone_number_type;
        }
        if ($request->phone_number) {
            $user->phone_number = $request->phone_number;
        }

        // Handle profile picture upload if present
        if ($request->hasFile('profile_picture')) {
            $imagePath = $request->file('profile_picture')->store('image', 'public');
            $user->profile_picture_url = $imagePath;
            
            if ($user->account_type == 'restaurant') {
                $restaurant = Restaurant::where('user_id', $user->id)->first();
                if ($restaurant) {
                    $restaurant->logo = $imagePath;
                    $restaurant->save();

             if ($request->hasFile('cover_picture')) {
                    $image = $request->file('cover_picture');
                    $image->store('image', 'public');
                    
                    $restaurant->cover_picture = $image;
                    $restaurant->save();
                }
                }
            }
        }

     

        $user->save();

        return response()->json([
            'message' => "Profile Updated successfully",
            'user' => $user,
            'profile_picture_url' => $user->profile_picture_url ? asset('public/storage/' . $user->profile_picture_url) : null,
            'cover_picture_url' => $user->account_type === 'restaurant' && $restaurant->cover_picture ? 
                asset('public/storage/' . $restaurant->cover_picture) : null
        ]);
    }

    /**
     * Get detailed statistics for admin dashboard
     */
    private function getAdminStatistics()
    {
        return [
            'orders' => [
                'today' => Order::whereDate('created_at', today())->count(),
                'week' => Order::whereDate('created_at', '>=', now()->subWeek())->count(),
                'month' => Order::whereDate('created_at', '>=', now()->subMonth())->count()
            ],
            'revenue' => [
                'today' => Transactions::whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('amount'),
                'week' => Transactions::whereDate('created_at', '>=', now()->subWeek())
                    ->where('status', 'completed')
                    ->sum('amount'),
                'month' => Transactions::whereDate('created_at', '>=', now()->subMonth())
                    ->where('status', 'completed')
                    ->sum('amount')
            ]
        ];
    }

    /**
     * Get user activity metrics
     */
    private function getUserActivityMetrics()
    {
        return [
            'new_users' => User::whereDate('created_at', '>=', now()->subDays(30))->count(),
            'active_users' => User::whereDate('updated_at', '>=', now()->subDays(7))->count(),
            'inactive_users' => User::whereDate('updated_at', '<', now()->subDays(30))->count()
        ];
    }
}
