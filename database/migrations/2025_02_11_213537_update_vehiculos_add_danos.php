<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVehiculosAddDanos extends Migration
{
    public function up()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->decimal('monto_danos', 10, 2)->nullable()->after('corralon');
            $table->text('partes_danadas')->nullable()->after('monto_danos');
        });
    }

    public function down()
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn(['monto_danos', 'partes_danadas']);
        });
    }
}
