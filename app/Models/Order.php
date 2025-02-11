<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    const ALLOWED_OPTIONS = ['pending','ready','completed','accepted'];

    protected $fillable = [
           'user_id',
            'items',
            'restaurant_id',
            'total',
            'status',
            'customer_location',
        
    
    ];

    protected $casts = [
        'items' => 'array',
    ];

    protected $hidden =
    [
        'created_at',
        'updated_at',
    ];


    // public function restaurant()
    // {
    //     return $this->belongsTo(Restaurant::class, 'restaurant_id');
    // }
}
