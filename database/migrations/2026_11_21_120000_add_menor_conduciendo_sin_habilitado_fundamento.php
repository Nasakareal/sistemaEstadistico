<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CODIGO = 'OP_CL_MENOR_CONDUCIENDO_SIN_HABILITADO';
    private const NOMBRE = 'Persona menor de edad conduciendo motocicleta y sin persona habilitada inmediata';
    private const FUNDAMENTO = 'Fundamento operativo compuesto: articulo 421, fraccion I, inciso a), relativo a la persona menor de edad que conduce motocicleta sin estar legalmente habilitada; articulos 700 y 702, relativos al marco de retiro o remision. No se documenta como causal automatica "menor de edad = deposito". La medida de retiro se asienta solo cuando no existe en el lugar una persona legalmente habilitada que pueda hacerse cargo inmediato y seguro de la motocicleta, para impedir la continuacion de la conducta.';

    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        $query = DB::table('licencia_punto_infracciones')
            ->where('codigo', self::CODIGO);
        $payload = [
                'nombre' => self::NOMBRE,
                'articulo' => '421; 700; 702',
                'fraccion' => 'I',
                'inciso' => 'a',
                'ambito_vehiculo' => 'motocicleta',
                'puntos' => 3,
                'multa_uma_min' => null,
                'multa_uma_max' => null,
                'amonestacion' => true,
                'arresto_persona' => false,
                'suspension_licencia' => false,
                'cancelacion_licencia' => false,
                'deposito_si_sin_persona_habilitada' => false,
                'retencion_vehiculo' => true,
                'descripcion' => self::NOMBRE,
                'fundamento_legal' => self::FUNDAMENTO,
                'activa' => true,
                'updated_at' => now(),
        ];

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
