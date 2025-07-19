<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('liberaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('hecho_id')->nullable()->after('vehiculo_id');
            $table->foreign('hecho_id')->references('id')->on('hechos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('liberaciones', function (Blueprint $table) {
            $table->dropForeign(['hecho_id']);
            $table->dropColumn('hecho_id');
        });
    }
};
