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
            Route::get('/restaurant-list', 'RestaurantController@restaurantList');



            Route::middleware('auth:sanctum')->group(function () {
                Route::get('/my-dashboard', 'ProfileController@myDashboard');
                Route::post('/logout', 'AuthController@logout');

            });
           

});