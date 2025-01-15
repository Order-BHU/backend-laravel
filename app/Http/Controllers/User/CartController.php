<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\User;
use App\Models\Menu;
use App\Models\Restaurant;

class CartController extends Controller
{

    public function addToCart(Request $request, $menuId, $quantity)
    {

        $cart = Cart::create([
            'menu_id' => $menuId,
            'quantity' => $quantity,
            'user_id' => $request->user()->id
        ]);



        return response()->json([
            'message' => "Item added to cart",
            'cart_id' => $cart->id
        ], 200);

    }

    public function viewCart(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->get();

        $cartList = [];
        foreach ($cart as $c){
            $menu = Menu::where('id', $c->menu_id)->first();

            $item = [
                'item_picture' => $menu->image,
                'item_name' => $menu->name,
                'item_description' => $menu->description,
                'quantity' => $c->quantity,
            ];
            array_push($item,$cartList);
        }

        return response()->json([
            'message' => 'Cart Items Below',
            'cart-items'=> $cartList
        ],200);
    }
    
}
