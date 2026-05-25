<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FomentoMunicipiosAtendidos2026Seeder extends Seeder
{
    private const ANIO = 2026;
    private const SOURCE_MARKER = 'MUNICIPIOS_ATENDIDOS_UFCV_2026_EXCEL';
    private const ACTIVITY_MARKER = 'SEEDER UFCV HISTORICO ENERO-MAYO 2026';
    private const TZ = 'America/Mexico_City';

    public function run(): void
    {
        $this->assertRequiredTable();

        $now = Carbon::now(self::TZ)->format('Y-m-d H:i:s');
        $rows = $this->rowsWithRemainders();

        DB::transaction(function () use ($rows, $now) {
            DB::table('fomento_municipios_atendidos_historicos')
                ->where('anio', self::ANIO)
                ->where('source_marker', self::SOURCE_MARKER)
                ->delete();

            foreach ($rows as $row) {
                DB::table('fomento_municipios_atendidos_historicos')->insert([
                    'anio' => self::ANIO,
                    'mes' => $row['mes'],
                    'municipio' => $row['municipio'],
                    'eventos' => $row['eventos'],
                    'poblacion_atendida' => $row['poblacion_atendida'],
                    'source_marker' => self::SOURCE_MARKER,
                    'activity_marker' => self::ACTIVITY_MARKER,
                    'notas' => $row['notas'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        if ($this->command) {
            $this->command->info('Municipios atendidos UFCV 2026 cargados correctamente.');
        }
    }

    private function assertRequiredTable(): void
    {
        if (!Schema::hasTable('fomento_municipios_atendidos_historicos')) {
            throw new RuntimeException('No existe la tabla fomento_municipios_atendidos_historicos. Ejecuta migraciones antes del seeder.');
        }
    }

    private function rowsWithRemainders(): array
    {
        $rows = $this->sourceRows();
        $expected = $this->expectedMonthTotals();
        $current = [];

        foreach ($expected as $mes => $totals) {
            $current[$mes] = [
                'eventos' => 0,
                'poblacion_atendida' => 0,
            ];
        }

        foreach ($rows as $row) {
            $current[$row['mes']]['eventos'] += $row['eventos'];
            $current[$row['mes']]['poblacion_atendida'] += $row['poblacion_atendida'];
        }

        foreach ($expected as $mes => $totals) {
            $eventosFaltantes = $totals['eventos'] - $current[$mes]['eventos'];
            $poblacionFaltante = $totals['poblacion_atendida'] - $current[$mes]['poblacion_atendida'];

            if ($eventosFaltantes < 0 || $poblacionFaltante < 0) {
                throw new RuntimeException("La matriz de municipios 2026 excede el total mensual del mes {$mes}.");
            }

            if ($eventosFaltantes > 0 || $poblacionFaltante > 0) {
                $rows[] = [
                    'mes' => $mes,
                    'municipio' => 'NO ESPECIFICADO',
                    'eventos' => $eventosFaltantes,
                    'poblacion_atendida' => $poblacionFaltante,
                    'notas' => 'Resto no cubierto por la matriz municipal proporcionada; conserva el total mensual del histórico 2026.',
                ];
            }
        }

        $this->assertRowsMatchExpectedTotals($rows, $expected);

        return $rows;
    }

    private function assertRowsMatchExpectedTotals(array $rows, array $expected): void
    {
        $actual = [];

        foreach ($expected as $mes => $totals) {
            $actual[$mes] = [
                'eventos' => 0,
                'poblacion_atendida' => 0,
            ];
        }

        foreach ($rows as $row) {
            $actual[$row['mes']]['eventos'] += $row['eventos'];
            $actual[$row['mes']]['poblacion_atendida'] += $row['poblacion_atendida'];
        }

        foreach ($expected as $mes => $totals) {
            foreach ($totals as $field => $expectedValue) {
                if ((int) $actual[$mes][$field] !== (int) $expectedValue) {
                    throw new RuntimeException("Total municipal 2026 incorrecto para mes {$mes}, campo {$field}: esperado {$expectedValue}, obtenido {$actual[$mes][$field]}.");
                }
            }
        }
    }

    private function expectedMonthTotals(): array
    {
        return [
            1 => ['eventos' => 98, 'poblacion_atendida' => 14548],
            2 => ['eventos' => 103, 'poblacion_atendida' => 18111],
            3 => ['eventos' => 87, 'poblacion_atendida' => 13913],
            4 => ['eventos' => 77, 'poblacion_atendida' => 15133],
            5 => ['eventos' => 20, 'poblacion_atendida' => 8031],
        ];
    }

    private function sourceRows(): array
    {
        return [
            $this->row(1, 'Morelia', 71, 10695),
            $this->row(1, 'Zamora', 21, 3268),
            $this->row(1, 'Vista Hermosa', 1, 423),
            $this->row(1, 'Pátzcuaro', 2, 75),
            $this->row(1, 'Tarímbaro', 1, 87),

            $this->row(2, 'Huandacareo', 5, 622),
            $this->row(2, 'Morelia', 62, 11901),
            $this->row(2, 'Vista Hermosa', 1, 91),
            $this->row(2, 'Tarímbaro', 1, 62),
            $this->row(2, 'Cuitzeo', 6, 1127),
            $this->row(2, 'Lázaro Cárdenas', 7, 786),

            $this->row(3, 'Morelia', 60, 10652),
            $this->row(3, 'Tarímbaro', 1, 100),
            $this->row(3, 'Cuitzeo', 2, 300),
            $this->row(3, 'Gabriel Zamora', 1, 165),
            $this->row(3, 'Zacapu', 6, 567),
            $this->row(3, 'Quiroga', 2, 300),
            $this->row(3, 'Angamacutiro', 2, 169),
            $this->row(3, 'Contepec', 3, 480),
            $this->row(3, 'Purúandiro', 2, 1118),
            $this->row(3, 'Acuitzio', 1, 62),

            $this->row(4, 'Huandacareo', 2, 1151),
            $this->row(4, 'Morelia', 58, 11574),
            $this->row(4, 'Zamora', 1, 338),
            $this->row(4, 'Pátzcuaro', 1, 80),
            $this->row(4, 'Acuitzio', 2, 82),
            $this->row(4, 'Charapan', 2, 700),
            $this->row(4, 'Hidalgo', 1, 40),
            $this->row(4, 'Madero', 2, 418),
            $this->row(4, 'Paracho', 1, 555),
            $this->row(4, 'Tacámbaro', 1, 195),

            $this->row(5, 'Huandacareo', 2, 1732),
            $this->row(5, 'Morelia', 16, 4279),
            $this->row(5, 'Ziracuaretiro', 2, 2020),
        ];
    }

    private function row(int $mes, string $municipio, int $eventos, int $poblacionAtendida): array
    {
        return [
            'mes' => $mes,
            'municipio' => mb_strtoupper($municipio, 'UTF-8'),
            'eventos' => $eventos,
            'poblacion_atendida' => $poblacionAtendida,
            'notas' => 'Matriz municipal proporcionada por UFCV para 2026.',
        ];
    }
}
