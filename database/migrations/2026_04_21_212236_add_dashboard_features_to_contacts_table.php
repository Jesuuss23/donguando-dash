<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('contacts', function (Blueprint $table) {
        $table->integer('unread_count')->default(0); // Mensajes sin leer
        $table->integer('ai_count_24h')->default(0); // Contador de respuestas IA
        $table->boolean('is_pinned')->default(false); // Para anclar chats
        $table->timestamp('last_message_at')->nullable(); // Para el ordenamiento
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
        });
    }
};
