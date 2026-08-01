<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('constancias_manejo', function (Blueprint $table) {
            $table->string('lote_uuid', 36)->nullable()->after('pdf_path')->index();
        });

        // Las constancias de un lote histórico comparten módulo, usuario y segundo de impresión.
        $grupos = DB::table('constancias_manejo')
            ->select(['modulo_id', 'user_id', 'fecha_impresion'])
            ->whereNull('lote_uuid')
            ->whereNotNull('fecha_impresion')
            ->groupBy(['modulo_id', 'user_id', 'fecha_impresion'])
            ->orderBy('fecha_impresion')
            ->get();

        foreach ($grupos as $grupo) {
            $query = DB::table('constancias_manejo')
                ->whereNull('lote_uuid')
                ->where('fecha_impresion', $grupo->fecha_impresion);

            $grupo->modulo_id === null
                ? $query->whereNull('modulo_id')
                : $query->where('modulo_id', $grupo->modulo_id);

            $grupo->user_id === null
                ? $query->whereNull('user_id')
                : $query->where('user_id', $grupo->user_id);

            $query->update(['lote_uuid' => (string) Str::uuid()]);
        }

        DB::table('constancias_manejo')
            ->whereNull('lote_uuid')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) {
                DB::table('constancias_manejo')
                    ->where('id', $id)
                    ->update(['lote_uuid' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('constancias_manejo', function (Blueprint $table) {
            $table->dropIndex(['lote_uuid']);
            $table->dropColumn('lote_uuid');
        });
    }
};
