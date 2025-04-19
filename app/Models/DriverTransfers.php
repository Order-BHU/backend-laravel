<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverTransfers extends Model
{
    protected $table = 'driver_transfers';

    protected $fillable = [
      'user_id',
      'status',
      'reference',
      'transfer_code'

    ];
}
