<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, 'amonestacion')) {
                $table->boolean('amonestacion')->default(false)->after('multa_uma_max')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'arresto_persona')) {
                $table->boolean('arresto_persona')->default(false)->after('amonestacion')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'deposito_si_sin_persona_habilitada')) {
                $table->boolean('deposito_si_sin_persona_habilitada')->default(false)->after('arresto_persona');
            }
        });

        if (Schema::hasTable('conduce_legalidad_vehiculos') && !Schema::hasColumn('conduce_legalidad_vehiculos', 'persona_habilitada_resguardo')) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->boolean('persona_habilitada_resguardo')->default(false)->after('motivo_retencion');
            });
        }

        $this->actualizarCatalogo();
    }

    public function down(): void
    {
        if (Schema::hasTable('conduce_legalidad_vehiculos') && Schema::hasColumn('conduce_legalidad_vehiculos', 'persona_habilitada_resguardo')) {
            Schema::table('conduce_legalidad_vehiculos', function (Blueprint $table) {
                $table->dropColumn('persona_habilitada_resguardo');
            });
        }

        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach ([
                'deposito_si_sin_persona_habilitada',
                'arresto_persona',
                'amonestacion',
            ] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function actualizarCatalogo(): void
    {
        foreach ([
            'multa_uma_min',
            'multa_uma_max',
            'amonestacion',
            'arresto_persona',
            'deposito_si_sin_persona_habilitada',
        ] as $column) {
            if (!Schema::hasColumn(self::TABLE, $column)) {
                return;
            }
        }

        $now = now();
        $rows = DB::table(self::TABLE)->get();

        foreach ($rows as $row) {
            $textoActual = implode(' ', [
                $row->nombre ?? '',
                $row->descripcion ?? '',
                $row->fundamento_legal ?? '',
            ]);
            $teniaUma = $row->multa_uma_min !== null || $row->multa_uma_max !== null;
            $amonestacion = $teniaUma
                || (bool) ($row->amonestacion ?? false)
                || $this->contiene($textoActual, 'AMONESTACION');
            $arresto = $teniaUma
                || (bool) ($row->arresto_persona ?? false)
                || $this->contiene($textoActual, 'ARRESTO');

            $payload = [
                'multa_uma_min' => null,
                'multa_uma_max' => null,
                'amonestacion' => $amonestacion,
                'arresto_persona' => $arresto,
                'deposito_si_sin_persona_habilitada' => $arresto || (bool) ($row->deposito_si_sin_persona_habilitada ?? false),
                'updated_at' => $now,
            ];

            if ($teniaUma || $amonestacion || $arresto || $this->contiene($textoActual, 'UMA')) {
                $payload['fundamento_legal'] = $this->fundamentoLegal($row, $payload);
            }

            DB::table(self::TABLE)
                ->where('id', $row->id)
                ->update($payload);
        }
    }

    private function fundamentoLegal(object $row, array $payload): string
    {
        $partes = [];
        $referencia = $this->referenciaLegal($row);
        if ($referencia !== '') {
            $partes[] = $referencia;
        }

        $sanciones = [];
        if (!empty($payload['amonestacion'])) {
            $sanciones[] = 'amonestacion a la persona';
        }

        if (!empty($payload['arresto_persona'])) {
            $sanciones[] = 'arresto de la persona';
        }

        $puntos = (int) ($row->puntos ?? 0);
        if ($puntos > 0) {
            $sanciones[] = $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos') . ' de penalizacion en la licencia para conducir';
        }

        if (!empty($row->retencion_vehiculo)) {
            $sanciones[] = 'remision o retiro del vehiculo al deposito';
        } elseif (!empty($payload['deposito_si_sin_persona_habilitada'])) {
            $sanciones[] = 'deposito del vehiculo cuando no se encuentre persona legalmente habilitada para hacerse cargo inmediato';
        }

        if ($sanciones !== []) {
            $partes[] = implode('; ', $sanciones) . '.';
        }

        return implode(': ', array_filter($partes))
            ?: 'Fundamentado en el Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.';
    }

    private function referenciaLegal(object $row): string
    {
        $partes = [];

        if (trim((string) ($row->articulo ?? '')) !== '') {
            $partes[] = 'Articulo ' . trim((string) $row->articulo);
        }

        if (trim((string) ($row->fraccion ?? '')) !== '') {
            $partes[] = 'fraccion ' . trim((string) $row->fraccion);
        }

        if (trim((string) ($row->inciso ?? '')) !== '') {
            $partes[] = 'inciso ' . trim((string) $row->inciso);
        }

        return implode(', ', $partes);
    }

    private function contiene(string $texto, string $needle): bool
    {
        return str_contains($this->normalizar($texto), $needle);
    }

    private function normalizar(string $texto): string
    {
        $texto = strtoupper(strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]));

        return preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto;
    }
};
