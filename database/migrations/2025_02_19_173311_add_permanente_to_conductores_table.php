<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermanenteToConductoresTable extends Migration
{
    public function up()
    {
        Schema::table('conductores', function (Blueprint $table) {
            $table->boolean('permanente')->default(false)->after('vigencia_licencia');
        });
    }

    public function down()
    {
        Schema::table('conductores', function (Blueprint $table) {
            $table->dropColumn('permanente');
        });
    }
}
