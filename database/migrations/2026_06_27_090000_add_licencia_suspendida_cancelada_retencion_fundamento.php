<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';
    private const CODE = 'ART328_FII_LICENCIA_SUSPENDIDA_CANCELADA';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $now = now();
        $exists = DB::table(self::TABLE)
            ->where('codigo', self::CODE)
            ->exists();

        DB::table(self::TABLE)->updateOrInsert(
            ['codigo' => self::CODE],
            array_merge([
                'nombre' => 'Licencia suspendida o cancelada',
                'articulo' => '328',
                'fraccion' => 'II',
                'inciso' => null,
                'puntos' => 0,
                'multa_uma_min' => null,
                'multa_uma_max' => null,
                'retencion_vehiculo' => true,
                'descripcion' => 'Persona conductora con licencia suspendida o cancelada.',
                'fundamento_legal' => 'Articulo 328, fraccion II: medida de seguridad de retiro de circulacion del vehiculo y resguardo en deposito autorizado cuando la licencia se encuentre suspendida o cancelada.',
                'activa' => true,
                'updated_at' => $now,
            ], $exists ? [] : ['created_at' => $now])
        );

        DB::table(self::TABLE)
            ->where('codigo', 'ART328_RETIRO_CIRCULACION')
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->where('codigo', self::CODE)
            ->delete();
    }
};
