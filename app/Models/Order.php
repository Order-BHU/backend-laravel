<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Restaurant;
use App\Models\User;

class Order extends Model
{
    protected $table = 'orders';

    const ALLOWED_OPTIONS = ['pending','ready','completed','delivering','accepted'];

    protected $fillable = [
           'user_id',
            'items',
            'restaurant_id',
            'total',
            'status',
            'customer_location',
            'code'
        
    
    ];

    protected $casts = [
        'items' => 'array',
    ];

    protected $hidden =
    [
        'updated_at',
    ];


    // public function restaurant()
    // {
    //     return $this->belongsTo(Restaurant::class, 'restaurant_id');
    // }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}
