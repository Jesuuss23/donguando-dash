<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaFaq extends Model
{
    protected $table = 'ia_faqs';
    protected $fillable = ['keywords', 'question', 'answer', 'category', 'priority', 'is_active', 'use_ai_fallback'];
    protected $casts = ['keywords' => 'array', 'is_active' => 'boolean', 'use_ai_fallback' => 'boolean'];
}