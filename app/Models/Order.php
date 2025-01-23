<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    const ALLOWED_OPTIONS = ['pending','ready','completed','accepted'];
    protected $hidden =
    [
        'created_at',
        'updated_at',
    ];
}
