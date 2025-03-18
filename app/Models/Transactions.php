<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'customer_id',
        'restaurant_id',
        'amount',
        'type',
        'status',
        'reference',
    ];
}
