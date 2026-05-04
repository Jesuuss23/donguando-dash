<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImagePreviewToMessagesTable extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->longText('image_preview')->nullable()->comment('Preview de imagen en base64');
            $table->string('image_url')->nullable()->comment('URL original de la imagen');
            $table->integer('image_size')->nullable()->comment('Tamaño en bytes');
            $table->string('file_name')->nullable()->comment('Nombre del archivo');
            $table->string('mime_type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['image_preview', 'image_url', 'image_size', 'file_name', 'mime_type']);
        });
    }
}