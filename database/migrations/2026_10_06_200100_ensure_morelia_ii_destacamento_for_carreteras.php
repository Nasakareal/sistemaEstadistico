<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $unidadId = DB::table('unidades')->where('slug', 'carreteras')->value('id');

        if (!$unidadId) {
            return;
        }

        $existe = DB::table('destacamentos')
            ->where('unidad_id', $unidadId)
            ->whereRaw("UPPER(TRIM(nombre)) = 'MORELIA II'")
            ->exists();

        if (!$existe) {
            DB::table('destacamentos')->insert([
                'unidad_id' => $unidadId,
                'clave' => 'MORELIA-II',
                'nombre' => 'MORELIA II',
                'municipio' => 'MORELIA',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Se conserva el catalogo porque puede quedar referenciado por datos reales.
    }
};
