<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CODIGO = 'OP_CL_SIN_LICENCIA_SIN_HABILITADO';
    private const NOMBRE = 'Persona sin licencia y sin persona habilitada inmediata';
    private const FUNDAMENTO = 'Fundamento operativo compuesto: articulo 402, relativo a que solo puede conducir quien cuente con licencia vigente expedida por autoridad competente; articulos 700 y 702, relativos a supuestos expresos de retiro o remision al deposito. No se documenta como causal automatica "sin licencia = deposito"; se documenta que la persona carece de habilitacion juridica para conducir y que la circulacion no puede continuar bajo su mando. La medida de retiro se asienta solo cuando no existe en el lugar persona legalmente habilitada para hacerse cargo inmediato del vehiculo y se adopta para poner fin a la continuacion de la conducta.';

    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        $payload = [
            'nombre' => self::NOMBRE,
            'articulo' => '402; 700; 702',
            'fraccion' => null,
            'inciso' => null,
            'puntos' => 0,
            'multa_uma_min' => null,
            'multa_uma_max' => null,
            'retencion_vehiculo' => true,
            'descripcion' => self::NOMBRE,
            'fundamento_legal' => self::FUNDAMENTO,
            'activa' => true,
            'updated_at' => now(),
        ];

        $query = DB::table('licencia_punto_infracciones')
            ->where('codigo', self::CODIGO);

        if ($query->exists()) {
            $query->update($payload);
            return;
        }

        DB::table('licencia_punto_infracciones')->insert(array_merge($payload, [
            'codigo' => self::CODIGO,
            'created_at' => now(),
        ]));
    }

    public function down(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        DB::table('licencia_punto_infracciones')
            ->where('codigo', self::CODIGO)
            ->update([
                'retencion_vehiculo' => false,
                'activa' => false,
                'updated_at' => now(),
            ]);
    }
};
