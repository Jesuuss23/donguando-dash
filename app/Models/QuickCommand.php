<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickCommand extends Model
{
    protected $table = 'quick_commands'; // ← Especificar la tabla
    
    protected $fillable = ['category_id', 'command', 'title', 'body', 'order'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}