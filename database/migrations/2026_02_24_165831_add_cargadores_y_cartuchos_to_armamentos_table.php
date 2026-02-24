<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('armamentos', function (Blueprint $table) {

            $table->unsignedTinyInteger('cargadores_cantidad')
                ->default(2)
                ->after('calibre');

            $table->unsignedSmallInteger('cartuchos_cantidad')
                ->default(60)
                ->after('cargadores_cantidad');

        });
    }

    public function down(): void
    {
        Schema::table('armamentos', function (Blueprint $table) {
            $table->dropColumn([
                'cargadores_cantidad',
                'cartuchos_cantidad'
            ]);
        });
    }
};
