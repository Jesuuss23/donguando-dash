<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIaColumnsToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'beneficio')) {
                $table->text('beneficio')->nullable()->comment('Beneficio/Uso del producto');
            }
            if (!Schema::hasColumn('products', 'psicologia_venta')) {
                $table->text('psicologia_venta')->nullable()->comment('Psicología de Venta');
            }
            if (!Schema::hasColumn('products', 'stock')) {
                $table->integer('stock')->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['beneficio', 'psicologia_venta', 'stock']);
        });
    }
}