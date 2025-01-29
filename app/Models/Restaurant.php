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
        'cover_picture'
    ];

   



    // public function order()
    // {
    //     return $this->hasMany(Order::class);
    // }

}
