<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





Route::get('/test', function () {
    return response()->json([
        'message' => 'This is a test route.',
        'status' => 'success'
    ]);
});

Route::namespace('App\Http\Controllers\User')->group(function () {

            Route::post('/register', 'AuthController@register');
            Route::post('/login', 'AuthController@login');
            Route::post('/verify-user', 'AuthController@verifyUser');
            Route::post('/get-otp', 'AuthController@getOtp');
            Route::post('/forgot-password', 'AuthController@forgotPassword');
            Route::post('/reset-password', 'AuthController@resetPassword');
            Route::get('locations', 'ProfileController@getLocations');


            Route::get('auth/google', 'AuthController@redirectToGoogle');
            Route::get('auth/google/callback', 'AuthController@handleGoogleCallback');


            Route::get('/categories', 'RestaurantController@categories');
            Route::get('/restaurant-list', 'RestaurantController@restaurantList');
            Route::get('{restaurantId}/menu-list', 'RestaurantController@menuList');



            Route::middleware('auth:sanctum')->group(function () {

                // Restaurant routes
                Route::post('{restaurantId}/add-menu', 'RestaurantController@addMenu');
                Route::post('{menuId}/edit-menu', 'RestaurantController@editMenu');
                Route::get('{restaurantId}/my-orders', 'OrderController@myOrders');
                Route::post('{menuId}/delete-menu', 'RestaurantController@deleteMenu');
                Route::post('{menuId}/update-availability', 'RestaurantController@updateAvailability');

                // Cart routes
                Route::post('{menuId}/add-to-cart', 'CartController@addToCart');
                Route::post('{menuId}/remove-cart-item', 'CartController@removeCartItem');
                Route::get('/view-cart', 'CartController@viewCart');

                // Admin Access routes
                Route::get('all-orders', 'OrderController@allOrders');
                Route::post('quick-changes', 'OrderController@quickChanges');
                Route::get('driver-list', 'OrderController@driverList');

                // Order routes
                Route::post('{restaurantId}/initialize-checkout', 'OrderController@initializeCheckout');
                Route::post('{restaurantId}/checkout', 'OrderController@checkout');
                Route::post('{status}/driver-status-update', 'OrderController@driverStatusUpdate');
                Route::post('{orderId}/{status}/update-order-status', 'OrderController@updateOrderStatus');
                Route::get('/track-order', 'OrderController@trackOrder');



                // Authentication routes
                Route::post('/logout', 'AuthController@logout');
                Route::get('/dashboard', 'ProfileController@myDashboard');
                Route::post('/update-profile-picture', 'ProfileController@updateProfilePicture');
                Route::post('/update-cover-picture', 'ProfileController@updateCoverPicture');
                Route::post('/edit-profile', 'ProfileController@editProfile'); // Added edit profile route
                Route::post('/change-password', 'AuthController@changePassword'); // Added change password route
          

                // Payment routes
                Route::get('/bank-list', 'PaymentController@bankList');
                Route::post('/resolve-bank', 'PaymentController@resolveBank'); // Added resolve bank
                Route::get('/transaction-list', 'PaymentController@transactionList');
                Route::post('/transfer-webhook', 'PaymentController@transferWebhook'); // Paystack transfer webhook

                // Contact routes
                Route::post('/contact', 'ContactController@submitContact');
                Route::post('{contactId}/update-contact-status', 'ContactController@updateStatus');
                Route::get('/contacts', 'ContactController@getContactList');

            });


        });