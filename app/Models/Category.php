<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Category extends Model
{
    protected $fillable = ['name', 'icon', 'slug', 'order'];
    
    public function quickResponses()
    {
        return $this->hasMany(QuickResponse::class);
    }
}