<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoriaToPersonalsTable extends Migration
{
    public function up()
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('categoria', 20)
                  ->default('OPERATIVO')
                  ->after('area');
        });
    }

    public function down()
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
}
