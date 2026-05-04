<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeFolioC5iNullableOnHechosTable extends Migration
{
    public function up()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('folio_c5i', 20)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->string('folio_c5i', 20)->nullable(false)->change();
        });
    }
}
