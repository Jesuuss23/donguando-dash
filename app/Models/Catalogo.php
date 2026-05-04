<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalogo extends Model
{
    protected $table = 'catalogos';
    
    protected $fillable = [
        'categoria', 'slug',
        'pdf_url', 'pdf_active', 'pdf_file_id',
        'imagen_url', 'imagen_active', 'imagen_file_id',
        'link_url', 'link_active',
        'order'
    ];
    
    public function pdfFile()
    {
        return $this->belongsTo(File::class, 'pdf_file_id');
    }
    
    public function imagenFile()
    {
        return $this->belongsTo(File::class, 'imagen_file_id');
    }
}