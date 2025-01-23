<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{

protected $table = 'cart';

protected $fillable = [
    'menu_id',
    'quantity',
    'user_id',
    'order_id',
    'restaurant_id'
];
}
