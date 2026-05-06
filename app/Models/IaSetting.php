<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaSetting extends Model
{
    protected $table = 'ia_settings';
    protected $fillable = ['key', 'value', 'description', 'type', 'condition', 'is_active', 'order'];
    protected $casts = ['is_active' => 'boolean'];
}
