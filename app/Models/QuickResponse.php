<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickResponse extends Model
{
    protected $fillable = ['title', 'body', 'category_id', 'command'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}