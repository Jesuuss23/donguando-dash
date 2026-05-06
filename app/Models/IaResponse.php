<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaResponse extends Model
{
    protected $table = 'ia_responses';
    protected $fillable = ['trigger', 'response', 'use_emojis', 'is_active'];
    protected $casts = ['use_emojis' => 'boolean', 'is_active' => 'boolean'];
}