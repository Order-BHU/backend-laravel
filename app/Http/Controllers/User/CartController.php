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

        $inCart = Cart::where('menu_id',$menuId)
                  ->where('user_id', $request->user()->id)->first();

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
            $cartItem->save();
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

            $imageUrl = asset('storage/' . $menu->image);

            $item = [
                'menu_id'=> $menu->id,
                'item_price'=> $menu->price,
                'item_picture' => $imageUrl,
                'item_name' => $menu->name,
                'item_description' => $menu->description,
                'quantity' => $c->quantity,
            ];
            array_push($cartList,$item);
        }
        
        $total_price = array_sum(array_map(function ($item) {
            return intval($item['item_price']) * intval($item['quantity']);
        }, $cartList));


        return response()->json([
            'message' => 'Cart Items Below',
            'cart_items'=> $cartList,
            'total' => $total_price
        ],200);
    }
    
}
