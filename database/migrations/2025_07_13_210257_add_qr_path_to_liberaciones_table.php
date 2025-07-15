<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQrPathToLiberacionesTable extends Migration
{
    public function up()
    {
        Schema::table('liberaciones', function (Blueprint $table) {
            $table->string('qr_path')->nullable()->after('pdf_gruas');
        });
    }

    public function down()
    {
        Schema::table('liberaciones', function (Blueprint $table) {
            $table->dropColumn('qr_path');
        });
    }

}
