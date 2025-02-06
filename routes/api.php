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
            Route::get('locations','ProfileController@getLocations');

            Route::get('/categories', 'RestaurantController@categories');
            Route::get('/restaurant-list', 'RestaurantController@restaurantList');
            Route::get('{restaurantId}/menu-list', 'RestaurantController@menuList');



            Route::middleware('auth:sanctum')->group(function () {

                // Restaurant routes
                Route::post('{restaurantId}/add-menu', 'RestaurantController@addMenu');
                Route::post('{menuId}/edit-menu', 'RestaurantController@editMenu');
                Route::get('{restaurantId}/my-orders', 'OrderController@myOrders');

                // Cart routes
                Route::post('{menuId}/add-to-cart', 'CartController@addToCart');
                Route::post('{menuId}/remove-cart-item', 'CartController@removeCartItem');
                Route::get('/view-cart', 'CartController@viewCart');

                // Order routes
                Route::post('{restaurantId}/checkout', 'OrderController@checkout');
                Route::post('{status}/driver-status-update', 'OrderController@driverStatusUpdate');
                Route::post('{orderId}/{status}/update-order-status', 'OrderController@updateOrderStatus');
                Route::get('{orderId}/track-order', 'OrderController@trackOrder');
                Route::get('/test', 'OrderController@test');



                // Authentication routes
                Route::post('/logout', 'AuthController@logout');
                Route::post('/update-profile-picture', 'ProfileController@updateProfilePicture');


            });
           

});