<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryIdToQuickResponsesTable extends Migration
{
    public function up()
    {
        Schema::table('quick_responses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('command')->nullable(); // Ej: /pollo, /cerdo
        });
    }

    public function down()
    {
        Schema::table('quick_responses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'command']);
        });
    }
}