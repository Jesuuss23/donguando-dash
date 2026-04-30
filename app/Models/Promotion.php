<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = ['type', 'command', 'title', 'caption', 'order', 'file_id'];
    
    public function file()
    {
        return $this->belongsTo(File::class);
    }
}