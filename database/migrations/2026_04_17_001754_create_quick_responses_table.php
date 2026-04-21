<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quick_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Ejemplo: "Información de precio"
            $table->text('body');    // Ejemplo: "El {producto} está a S/ {precio} el kg."
            $table->timestamps();
            });       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_responses');
    }
};
