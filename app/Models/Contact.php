<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name', 
        'whatsapp_id', 
        'is_intervened',
        'ia_messages_count',   // ← AGREGAR ESTO
        'ia_last_reset',        // ← AGREGAR ESTO
        'is_pinned'
    ];

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function incrementIaCount()
    {
        $this->ia_messages_count = ($this->ia_messages_count ?? 0) + 1;
        $this->save();
    }
}