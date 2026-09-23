<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';
    private const EXISTING_LICENSE_CODE = 'ART328_FII_LICENCIA_SUSPENDIDA_CANCELADA';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $now = now();
        foreach ($this->fundamentos() as $row) {
            $exists = DB::table(self::TABLE)->where('codigo', $row['codigo'])->exists();
            $payload = array_merge($row, ['updated_at' => $now]);

            if ($exists) {
                DB::table(self::TABLE)->where('codigo', $row['codigo'])->update($payload);
            } else {
                DB::table(self::TABLE)->insert(array_merge($payload, ['created_at' => $now]));
            }
        }

        DB::table(self::TABLE)
            ->where('codigo', 'ART440_FI_MOTO_ACERAS_PEATONES')
            ->update([
                'retencion_vehiculo' => true,
                'fundamento_legal' => 'Reglamento de la Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulos 440, fraccion I, y 702, fraccion I, inciso d): procede el retiro de la motocicleta que circule sobre aceras o areas reservadas a personas peatonas.',
                'activa' => true,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $newCodes = array_values(array_filter(
            array_column($this->fundamentos(), 'codigo'),
            fn (string $codigo) => $codigo !== self::EXISTING_LICENSE_CODE
        ));

        DB::table(self::TABLE)
            ->whereIn('codigo', $newCodes)
            ->delete();

        DB::table(self::TABLE)
            ->where('codigo', self::EXISTING_LICENSE_CODE)
            ->update([
                'activa' => false,
                'updated_at' => now(),
            ]);

        DB::table(self::TABLE)
            ->where('codigo', 'ART440_FI_MOTO_ACERAS_PEATONES')
            ->update([
                'retencion_vehiculo' => false,
                'updated_at' => now(),
            ]);
    }

    private function fundamentos(): array
    {
        return [
            $this->row(
                'ART333_RIESGO_GRAVE_CONDUCCION_CONDICION',
                '333',
                null,
                'Conduccion o condicion fisica evidentemente peligrosa que representa grave riesgo',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 333: los elementos legalmente facultados deberan retirar de la circulacion los vehiculos cuya conduccion o condicion fisica evidentemente peligrosa represente un grave riesgo para las personas peatonas, sus ocupantes o los demas vehiculos.'
            ),
            $this->row(
                'ART328_FI_SIN_PLACAS_PERMISO_ALTERADAS_OBSTRUIDAS',
                '328',
                'I',
                'Sin ambas placas o permiso temporal; placas alteradas u obstruidas',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion I.'
            ),
            $this->row(
                'ART328_FII_LICENCIA_SUSPENDIDA_CANCELADA',
                '328',
                'II',
                'Licencia suspendida o cancelada',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion II.'
            ),
            $this->row(
                'ART328_FV_SIN_TARJETA_CIRCULACION_CONSTANCIA',
                '328',
                'V',
                'Sin tarjeta de circulacion ni constancia de robo o extravio',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion V.'
            ),
            $this->row(
                'ART328_FVII_USO_DISTINTO_AUTORIZADO',
                '328',
                'VII',
                'Vehiculo utilizado para fines distintos a los autorizados',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion VII.'
            ),
            $this->row(
                'ART328_FVIII_EMISION_HUMO_NOTORIA',
                '328',
                'VIII',
                'Emision de humo visiblemente notoria',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion VIII.'
            ),
            $this->row(
                'ART328_FXV_BAJA_ADMINISTRATIVA_SIN_PERMISO',
                '328',
                'XV',
                'Vehiculo con baja administrativa y sin permiso para circular',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion XV.'
            ),
            $this->row(
                'ART328_FXVI_INSTRUMENTO_OBJETO_DELITO',
                '328',
                'XVI',
                'Vehiculo instrumento u objeto de delito',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion XVI.'
            ),
            $this->row(
                'ART328_FXVIII_ORDEN_JUDICIAL_ADMINISTRATIVA',
                '328',
                'XVIII',
                'Retiro por orden judicial o administrativa',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion XVIII.'
            ),
            $this->row(
                'ART328_FXIX_CONDUCTOR_APREHENSION_SIN_RESGUARDO',
                '328',
                'XIX',
                'Aprehension, arresto o comparecencia del conductor sin persona para resguardar la unidad',
                'Ley de Movilidad y Seguridad Vial del Estado de Michoacan de Ocampo, articulo 328, fraccion XIX.'
            ),
        ];
    }

    private function row(
        string $codigo,
        string $articulo,
        ?string $fraccion,
        string $nombre,
        string $fundamentoLegal
    ): array {
        return [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'articulo' => $articulo,
            'fraccion' => $fraccion,
            'inciso' => null,
            'ambito_vehiculo' => 'general',
            'puntos' => 0,
            'multa_uma_min' => null,
            'multa_uma_max' => null,
            'amonestacion' => false,
            'arresto_persona' => false,
            'suspension_licencia' => false,
            'cancelacion_licencia' => false,
            'deposito_si_sin_persona_habilitada' => false,
            'retencion_vehiculo' => true,
            'descripcion' => $nombre,
            'fundamento_legal' => $fundamentoLegal,
            'activa' => true,
        ];
    }
};
