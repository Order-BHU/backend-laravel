<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\User;
use App\Models\Category;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function restaurantList()
    {
       $allRestaurants = Restaurant::select('id', 'name', 'logo')
    ->whereIn('id', [16])
    ->get();

        // Update the logo field to include the full URL path
        $allRestaurants->map(function ($restaurant) {
            $restaurant->logo = asset('public/storage/' . $restaurant->logo);
            return $restaurant;
        });

        return response()->json([
            'restaurant_list' => $allRestaurants
        ]);
    }

    public function categories()
    {
        $categories = Category::select('id', 'name')->get();

        return response()->json([
            'categories' => $categories
        ]);
    }


    public function menuList($restaurantId)
    {



        $categories = Category::select('name', 'id')
            ->with([
                'menus' => function ($query) use ($restaurantId) {
                    $query->select('id', 'name', 'description', 'restaurant_id', 'category_id', 'is_available', 'image', 'price')
                        ->where('restaurant_id', $restaurantId);
                }
            ])
            ->get()
            ->map(function ($category) {
                $category->menus = $category->menus->map(function ($menu) {
                    $menu->image = asset('public/storage/' . $menu->image); // Image Path works only online
                    return $menu;
                });
                return $category;
            });

        $restaurant = Restaurant::where('id', $restaurantId)->first();



        return response()->json([
            'cover_picture' =>$restaurant->cover_picture ? asset('public/storage/' . $restaurant->cover_picture): null,
            'restaurant' => $restaurant,
            'menu' => $categories,
        ], 200);
    }

    public function addMenu(Request $request, $restaurantId)
    {
        if ($request->user()->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $request->user()->id)->first();

            if ($request->user()->id != $restaurant->user_id) {
                return response()->json([
                    'message' => "Your not the restaurant owner"
                ]);
            }

            $request->validate([
                'name' => 'required|string',
                'description' => 'required|string',
                'category_id' => 'required',
                'price' => 'required',
                'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg'
            ]);

            // Handle image upload
            $imagePath = $request->file('image')->store('image', 'public');

            // Create the menu
            $menu = Menu::create([
                'name' => $request->name,
                'description' => $request->description,
                'restaurant_id' => $restaurantId,
                'is_available'=> 1,
                'price' => $request->price,
                'category_id' => $request->category_id,
                'image' => $imagePath
            ]);

            return response()->json([
                'message' => 'Menu created successfully!',
                'menu' => $menu
            ], 201);
        } else {
            return response()->json([
                'message' => 'User has no acess'
            ], 500);
        }


    }

    public function editMenu(Request $request, $menuId)
    {

        if ($request->user()->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $request->user()->id)->first();

            if ($request->user()->id != $restaurant->user_id) {
                return response()->json([
                    'message' => "Your not the restaurant owner"
                ]);
            }

            // Find the menu by ID
            $menu = Menu::where('id', $menuId)->first();

            // Check if the menu exists
            if (!$menu) {
                return response()->json([
                    'message' => 'Menu not found.'
                ], 404);
            }

            // Validate only provided fields
            $validatedData = $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'price' => 'nullable|numeric',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg',
            ]);

            // Update only the fields that exist in the validated data
            foreach ($validatedData as $key => $value) {
                if ($key === 'image' && $request->hasFile('image')) {
                    // Handle image upload
                    $menu->image = $request->file('image')->store('image', 'public');
                } else if ($key) {
                    // Update other fields
                    $menu->$key = $value;
                }
            }

            // Save the updated menu
            $menu->save();

            // Return a success response
            return response()->json([
                'message' => 'Menu updated successfully!',
                'menu' => $menu
            ]);
        }
    }

    public function deleteMenu($menuId)
    {

        $user = auth()->user();
        if ($user->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $user->id)->first();

            if ($user->id != $restaurant->user_id) {
                return response()->json([
                    'message' => "Your not the restaurant owner"
                ]);
            }

            $menu = Menu::where('id', $menuId)->first();

            if (!$menu) {
                return response()->json([
                    'message' => 'Menu not found.'
                ], 404);
            }

            $menu->delete();

            return response()->json([
                'message' => 'Menu deleted successfully!'
            ]);



        }

    }

    public function updateAvailability(Request $request, $menuId)
    {
        // Validate the request, expecting a boolean for 'available'
        $data = $request->validate([
            'is_available' => 'required|boolean',
        ]);

        // Fetch the menu item (assuming you have a Menu model)
        $menu = Menu::findOrFail($menuId);

        if (!$menu) {
            return response()->json([
                'message' => 'Menu not found.'
            ], 404);
        }

        // Update the availability status
        $menu->is_available = $data['is_available'];
        $menu->save();

        return response()->json([
            'message' => 'Menu availability updated successfully',
            'menu' => $menu
        ]);
    }
}
