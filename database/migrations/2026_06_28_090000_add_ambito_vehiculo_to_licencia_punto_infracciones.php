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

        if (!Schema::hasColumn(self::TABLE, 'ambito_vehiculo')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->string('ambito_vehiculo', 50)->nullable()->after('inciso')->index('lp_infracciones_ambito_idx');
            });
        }

        $now = now();
        DB::table(self::TABLE)
            ->orderBy('id')
            ->get()
            ->each(function ($row) use ($now) {
                DB::table(self::TABLE)
                    ->where('id', $row->id)
                    ->update([
                        'ambito_vehiculo' => $this->inferirAmbito($row),
                        'updated_at' => $now,
                    ]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'ambito_vehiculo')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropIndex('lp_infracciones_ambito_idx');
            $table->dropColumn('ambito_vehiculo');
        });
    }

    private function inferirAmbito(object $row): string
    {
        $articulo = trim((string) ($row->articulo ?? ''));
        $fraccion = trim((string) ($row->fraccion ?? ''));
        $texto = $this->normalizar(implode(' ', [
            $articulo,
            $fraccion,
            $row->codigo ?? '',
            $row->nombre ?? '',
            $row->descripcion ?? '',
            $row->fundamento_legal ?? '',
        ]));

        if ($this->contieneFrase($texto, 'NO MOTORIZADO')) {
            return 'no_motorizado';
        }

        if ($articulo === '440' || ($articulo === '420' && $fraccion === 'III') || $this->contieneFrase($texto, 'MOTOCICLETA') || $this->contienePalabra($texto, 'MOTO')) {
            return 'motocicleta';
        }

        if (
            $this->contieneFrase($texto, 'SUSTANCIAS')
            || $this->contieneFrase($texto, 'TOXICAS')
            || $this->contieneFrase($texto, 'PELIGROSAS')
            || $this->contieneFrase($texto, 'INFLAMABLES')
            || $this->contieneFrase($texto, 'EXPLOSIVAS')
        ) {
            return 'sustancias_peligrosas';
        }

        if (($articulo === '420' && $fraccion === 'V') || $this->contienePalabra($texto, 'CARGA')) {
            return 'carga';
        }

        if (
            $this->contieneFrase($texto, 'TRANSPORTE PUBLICO')
            || $this->contienePalabra($texto, 'OPERADOR')
            || $this->contienePalabra($texto, 'OPERADORA')
            || $this->contieneFrase($texto, 'TRANSPORTE ESCOLAR')
            || $this->contieneFrase($texto, 'DE PERSONAL')
        ) {
            return 'transporte_publico';
        }

        if (
            $this->contieneFrase($texto, 'SINIESTRO')
            || $this->contienePalabra($texto, 'PERITO')
            || $this->contieneFrase($texto, 'REPARACION DEL DANO')
        ) {
            return 'siniestro';
        }

        if (
            $articulo === '419'
            || $this->contienePalabra($texto, 'CONDUCTOR')
            || $this->contienePalabra($texto, 'CONDUCTORA')
            || $this->contieneFrase($texto, 'MOTORIZADO')
            || $this->contieneFrase($texto, 'AUTOMOTOR')
            || $this->contieneFrase($texto, 'PARTICULAR')
        ) {
            return 'automovil';
        }

        return 'general';
    }

    private function contieneFrase(string $texto, string $needle): bool
    {
        return str_contains($texto, $needle);
    }

    private function contienePalabra(string $texto, string $needle): bool
    {
        return preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $texto) === 1;
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
        $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto;

        return preg_replace('/\s+/', ' ', trim($texto)) ?? $texto;
    }
};
