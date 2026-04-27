<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 
        'price', 
        'unit',
        'stock',
        'beneficio',
        'psicologia_venta'
    ];
}