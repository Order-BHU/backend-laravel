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

    public function addToCart(Request $request, $menuId)
    {

        $inCart = Cart::where('menu_id',$menuId)->first();

        if(!$inCart){
            $cart = Cart::create([
                'menu_id' => $menuId,
                'quantity' => 1,
                'user_id' => $request->user()->id
            ]);
        }
        else{
            $inCart->quantity = $inCart->quantity + 1;
            $inCart->save(); 
        }

        return response()->json([
            'message' => "Item added to cart",
            
        ], 200);

    }

    public function removeCartItem(Request $request,$menuId){
        $cartItem = Cart::where('menu_id',$menuId)->first();

        if(!$cartItem) {
            return response()->json([
                'error' => 'Cart Item not found'
            ], 404);
        }
        if($cartItem->quantity == 1  )   {
        $cartItem->delete();
        }
        else{
            $cartItem->quantity = $cartItem->quantity - 1;
        }

        return response()->json([
            'message' => "Cart Item removed",
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
