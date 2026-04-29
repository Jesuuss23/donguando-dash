<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickCommandsTable extends Migration
{
    public function up()
    {
        Schema::create('quick_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('command')->nullable(); // ej: /cerdo
            $table->string('title'); // Título visible
            $table->text('body'); // Mensaje a enviar
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quick_commands');
    }
}