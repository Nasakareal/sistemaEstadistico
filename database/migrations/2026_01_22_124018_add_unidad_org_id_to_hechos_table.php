<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->foreignId('unidad_org_id')
                ->nullable()
                ->after('unidad')
                ->constrained('unidades')
                ->nullOnDelete()
                ->index();
        });
    }

    public function down()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidad_org_id');
        });
    }
};
