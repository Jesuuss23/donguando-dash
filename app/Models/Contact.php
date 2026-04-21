<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['name', 'whatsapp_id', 'is_intervened'];

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function tags()
{
    return $this->belongsToMany(Tag::class);
}
}