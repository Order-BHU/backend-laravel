<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $table = 'restaurants';

    protected $fillable = [
        'name',
        'user_id',
        'logo',
        'cover_picture',
        'account_no',
        'bank_name',
        'bank_code',
        'subaccount_code'
    ];

   



    // public function order()
    // {
    //     return $this->hasMany(Order::class);
    // }

}
