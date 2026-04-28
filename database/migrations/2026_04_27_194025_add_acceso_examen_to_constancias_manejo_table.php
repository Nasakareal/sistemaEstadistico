<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccesoExamenToConstanciasManejoTable extends Migration
{
    public function up()
    {
        Schema::table('constancias_manejo', function (Blueprint $table) {
            $table->string('acceso_examen_token', 100)->nullable()->unique()->after('qr_token');
            $table->dateTime('acceso_examen_expira')->nullable()->after('acceso_examen_token');
        });
    }

    public function down()
    {
        Schema::table('constancias_manejo', function (Blueprint $table) {
            $table->dropColumn([
                'acceso_examen_token',
                'acceso_examen_expira',
            ]);
        });
    }
}
