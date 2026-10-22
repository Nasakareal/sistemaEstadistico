<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->boolean('operativa')->default(true)->after('activa');
        });

        DB::table('delegaciones')
            ->whereNull('delegacion_padre_id')
            ->whereIn(DB::raw('UPPER(nombre)'), ['ZAMORA', 'LA PIEDAD'])
            ->update(['operativa' => false]);
    }

    public function down(): void
    {
        Schema::table('delegaciones', function (Blueprint $table) {
            $table->dropColumn('operativa');
        });
    }
};
