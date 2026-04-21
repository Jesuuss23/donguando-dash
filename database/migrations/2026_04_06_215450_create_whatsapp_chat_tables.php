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
    Schema::create('contacts', function (Blueprint $table) {
        $table->id();
        $table->string('whatsapp_id')->unique(); // El número del cliente
        $table->string('name')->nullable();
        $table->boolean('is_intervened')->default(false); // <--- TU BOTÓN MÁGICO
        $table->timestamps();
    });

    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('contact_id')->constrained()->onDelete('cascade');
        $table->text('body');
        $table->boolean('from_me')->default(false); // 0 = Cliente, 1 = Nosotros
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_tables');
    }
};
