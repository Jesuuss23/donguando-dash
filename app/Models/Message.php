<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
    'contact_id', 
    'body', 
    'from_me',
    'image_preview',
    'image_url', 
    'image_size',
    'file_name',
    'mime_type'
];
protected $casts = [
    'image_size' => 'integer',
    'from_me' => 'boolean'
];
    public function contact() {
        return $this->belongsTo(Contact::class);
    }
}