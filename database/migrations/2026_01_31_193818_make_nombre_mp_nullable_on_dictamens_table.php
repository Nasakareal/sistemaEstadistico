<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('dictamens', function (Blueprint $table) {
            $table->string('nombre_mp', 100)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('dictamens', function (Blueprint $table) {
            $table->string('nombre_mp', 100)->nullable(false)->change();
        });
    }
};
