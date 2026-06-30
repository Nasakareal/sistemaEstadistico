<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $now = now();

        DB::table(self::TABLE)
            ->whereIn('codigo', [
                'ART419_FII_I_MOTO_LUCES_CASCO_DECRETO',
                'ART419_FII_I_MOTO_PASAJEROS_CASCO_DECRETO',
                'ART419_FII_IA_C_MOTO_LUCES_CASCO_DECRETO',
                'ART419_FII_IB_D_MOTO_PASAJEROS_CASCO_DECRETO',
            ])
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);

        foreach ($this->rows() as $row) {
            $exists = DB::table(self::TABLE)->where('codigo', $row['codigo'])->exists();

            DB::table(self::TABLE)->updateOrInsert(
                ['codigo' => $row['codigo']],
                array_merge($row, [
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        DB::table(self::TABLE)
            ->whereIn('codigo', array_column($this->rows(), 'codigo'))
            ->delete();

        DB::table(self::TABLE)
            ->whereIn('codigo', [
                'ART419_FII_I_MOTO_LUCES_CASCO_DECRETO',
                'ART419_FII_I_MOTO_PASAJEROS_CASCO_DECRETO',
                'ART419_FII_IA_C_MOTO_LUCES_CASCO_DECRETO',
                'ART419_FII_IB_D_MOTO_PASAJEROS_CASCO_DECRETO',
            ])
            ->update([
                'activa' => true,
                'updated_at' => now(),
            ]);
    }

    private function rows(): array
    {
        return [
            $this->row(
                'ART419_FII_IA_MOTO_LUCES_DECRETO',
                '419',
                'II',
                'a',
                'Motocicleta sin luces delanteras y traseras encendidas',
                1,
                true,
                false,
                false,
                false,
                false,
                false,
                'Articulo 419, fraccion II, inciso a: circular todo el tiempo con las luces traseras y delanteras encendidas. La infraccion se sanciona con amonestacion y un punto de penalizacion a la licencia para conducir.'
            ),
            $this->row(
                'ART419_FII_IC_MOTO_REFLEJANTES_DECRETO',
                '419',
                'II',
                'c',
                'Motocicleta sin aditamentos luminosos o bandas reflejantes en horario nocturno',
                1,
                true,
                false,
                false,
                false,
                false,
                false,
                'Articulo 419, fraccion II, inciso c: usar aditamentos luminosos o bandas reflejantes en horario nocturno. La infraccion se sanciona con amonestacion y un punto de penalizacion a la licencia para conducir.'
            ),
            $this->row(
                'ART419_FII_IB_MOTO_EXCESO_PERSONAS_DECRETO',
                '419',
                'II',
                'b',
                'Motocicleta con exceso de personas conforme a tarjeta de circulacion',
                3,
                false,
                true,
                false,
                false,
                true,
                true,
                'Articulo 419, fraccion II, inciso b: llevar a bordo solo la cantidad de personas que senale la tarjeta de circulacion. La infraccion se sanciona con arresto hasta por 36 horas, tres puntos de penalizacion a la licencia para conducir y remision de la motocicleta al deposito.'
            ),
            $this->row(
                'ART419_FII_ID_MOTO_CASCO_PROTECTOR_DECRETO',
                '419',
                'II',
                'd',
                'Motocicleta sin casco protector conforme a especificaciones',
                3,
                false,
                true,
                false,
                false,
                true,
                true,
                'Articulo 419, fraccion II, inciso d: la persona conductora y acompanante deben utilizar casco protector para motocicleta conforme a especificaciones de seguridad. La infraccion se sanciona con arresto hasta por 36 horas, tres puntos de penalizacion a la licencia para conducir y remision de la motocicleta al deposito.'
            ),
        ];
    }

    private function row(
        string $codigo,
        string $articulo,
        string $fraccion,
        string $inciso,
        string $nombre,
        int $puntos,
        bool $amonestacion,
        bool $arrestoPersona,
        bool $suspensionLicencia,
        bool $cancelacionLicencia,
        bool $depositoSiSinPersonaHabilitada,
        bool $retencionVehiculo,
        string $fundamentoLegal
    ): array {
        return [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'articulo' => $articulo,
            'fraccion' => $fraccion,
            'inciso' => $inciso,
            'ambito_vehiculo' => 'motocicleta',
            'puntos' => $puntos,
            'multa_uma_min' => null,
            'multa_uma_max' => null,
            'amonestacion' => $amonestacion,
            'arresto_persona' => $arrestoPersona,
            'suspension_licencia' => $suspensionLicencia,
            'cancelacion_licencia' => $cancelacionLicencia,
            'deposito_si_sin_persona_habilitada' => $depositoSiSinPersonaHabilitada,
            'retencion_vehiculo' => $retencionVehiculo,
            'descripcion' => $nombre,
            'fundamento_legal' => $fundamentoLegal,
            'activa' => true,
        ];
    }
};