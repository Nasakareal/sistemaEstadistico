<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCapturaUuidAndHoraToOperativosTable extends Migration
{
    public function up()
    {
        Schema::table('operativos', function (Blueprint $table) {
            $table->uuid('captura_uuid')->nullable()->after('id')->index();
            $table->time('hora')->nullable()->after('fecha');
        });
    }

    public function down()
    {
        Schema::table('operativos', function (Blueprint $table) {
            $table->dropIndex(['captura_uuid']);
            $table->dropColumn(['captura_uuid', 'hora']);
        });
    }
}
