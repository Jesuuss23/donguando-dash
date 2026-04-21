<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['contact_id', 'body', 'from_me'];

    public function contact() {
        return $this->belongsTo(Contact::class);
    }
}