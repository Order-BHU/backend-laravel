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
                'user_id' => $request->user()->id
            ]);
        }
        else{
           return response()->json([
            'message' => 'Item already in cart',
           ]);
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
        
        $cartItem->delete();
    
      
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

            $imageUrl = asset('public/storage/' . $menu->image);

            $item = [
                'menu_id'=> $menu->id,
                'item_price'=> $menu->price,
                'is_available'=> $menu->is_available,
                'item_picture' => $imageUrl,
                'item_name' => $menu->name,
                'item_description' => $menu->description,
            ];
            array_push($cartList,$item);
        }
        
    


        return response()->json([
            'message' => 'Cart Items Below',
            'cart_items'=> $cartList,
        ],200);
    }
    
}
