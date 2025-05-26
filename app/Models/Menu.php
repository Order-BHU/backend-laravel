<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Category;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = [
            'name',
            'description',
            'price',
            'restaurant_id',
            'is_available',
            'category_id',
            'image'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }



}
