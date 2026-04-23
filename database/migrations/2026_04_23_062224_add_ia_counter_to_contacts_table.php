<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIaCounterToContactsTable extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'ia_messages_count')) {
                $table->integer('ia_messages_count')->default(0);
            }
            if (!Schema::hasColumn('contacts', 'ia_last_reset')) {
                $table->timestamp('ia_last_reset')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['ia_messages_count', 'ia_last_reset']);
        });
    }
}