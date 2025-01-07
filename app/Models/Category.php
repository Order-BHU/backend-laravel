<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Menu;

class Category extends Model
{
    protected $table = 'categories';

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

}
