<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKmRecorridosToActividadesTable extends Migration
{
    public function up()
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->decimal('km_recorridos', 10, 2)->nullable()->after('lng');
        });
    }

    public function down()
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn('km_recorridos');
        });
    }
}
