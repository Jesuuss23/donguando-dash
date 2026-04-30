<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = ['original_name', 'saved_as', 'mime_type', 'size'];
    
    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }
}