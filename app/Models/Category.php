<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories'; // ← Especificar la tabla
    
    protected $fillable = ['name', 'icon', 'slug', 'order'];
    
    public function quickCommands()
    {
        return $this->hasMany(QuickCommand::class);
    }
}