<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDictamensTable extends Migration
{
    public function up()
    {
        Schema::create('dictamens', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_dictamen')->unsigned();
            $table->year('anio');
            $table->string('nombre_policia', 100);
            $table->string('nombre_mp', 100);
            $table->string('area', 100);
            $table->string('archivo_dictamen')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            $table->unique(['numero_dictamen', 'anio', 'area']);
        });
    }

    public function down()
    {
        if (Schema::hasTable('dictamens')) {
            Schema::table('dictamens', function (Blueprint $table) {
                if (Schema::hasColumn('dictamens', 'created_by')) {
                    $table->dropForeign(['created_by']);
                }

                if (Schema::hasColumn('dictamens', 'updated_by')) {
                    $table->dropForeign(['updated_by']);
                }
            });
        }

        Schema::dropIfExists('dictamens');
    }
}
