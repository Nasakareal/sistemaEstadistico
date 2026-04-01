<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destacamentos', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('municipio');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->string('direccion')->nullable()->after('lng');
            $table->string('telefono', 30)->nullable()->after('direccion');
            $table->string('responsable')->nullable()->after('telefono');
            $table->string('referencia')->nullable()->after('responsable');
        });
    }

    public function down(): void
    {
        Schema::table('destacamentos', function (Blueprint $table) {
            $table->dropColumn([
                'lat',
                'lng',
                'direccion',
                'telefono',
                'responsable',
                'referencia',
            ]);
        });
    }
};
