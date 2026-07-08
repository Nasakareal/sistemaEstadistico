<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('patrullas', function (Blueprint $table) {
            if (!Schema::hasColumn('patrullas', 'resguardo_pdf')) {
                $table->string('resguardo_pdf')->nullable()->after('foto');
            }
        });
    }

    public function down()
    {
        Schema::table('patrullas', function (Blueprint $table) {
            if (Schema::hasColumn('patrullas', 'resguardo_pdf')) {
                $table->dropColumn('resguardo_pdf');
            }
        });
    }
};
