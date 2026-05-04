<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatalogosTable extends Migration
{
    public function up()
    {
        Schema::create('catalogos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria'); 
            $table->string('slug')->unique();
            
            // PDF
            $table->string('pdf_url')->nullable();
            $table->boolean('pdf_active')->default(false);
            $table->string('pdf_file_id')->nullable(); 
            
            // Imagen
            $table->string('imagen_url')->nullable();
            $table->boolean('imagen_active')->default(false);
            $table->string('imagen_file_id')->nullable();
            
            // Link
            $table->string('link_url')->nullable();
            $table->boolean('link_active')->default(false);
            
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalogos');
    }
}