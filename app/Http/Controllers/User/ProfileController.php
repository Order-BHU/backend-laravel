<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function myDashboard(Request $request){
        return response()->json([
            'message'=> $request->user()
        ]);
    }
}
