<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoordinatesToDelegacionesTable extends Migration
{
    public function up()
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('municipio');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down()
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
}
