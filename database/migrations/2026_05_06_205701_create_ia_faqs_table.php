<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ia_faqs', function (Blueprint $table) {
            $table->id();
            $table->json('keywords')->nullable();
            $table->text('question')->nullable();
            $table->text('answer');
            $table->string('category')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('use_ai_fallback')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ia_faqs');
    }
};