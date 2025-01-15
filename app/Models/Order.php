<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $hidden =
    [
        'created_at',
        'updated_at',
    ];
}
