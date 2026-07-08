<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('patrullas', function (Blueprint $table) {
            if (!Schema::hasColumn('patrullas', 'resguardo_nombre')) {
                $table->string('resguardo_nombre', 150)->nullable()->after('resguardo_pdf');
            }
        });
    }

    public function down()
    {
        Schema::table('patrullas', function (Blueprint $table) {
            if (Schema::hasColumn('patrullas', 'resguardo_nombre')) {
                $table->dropColumn('resguardo_nombre');
            }
        });
    }
};
