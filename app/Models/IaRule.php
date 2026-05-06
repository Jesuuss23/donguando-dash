<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaRule extends Model
{
    protected $table = 'ia_rules';
    protected $fillable = ['name', 'condition', 'action', 'message', 'is_active', 'priority'];
    protected $casts = ['is_active' => 'boolean'];
}