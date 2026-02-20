<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RecomputeRiskZones extends Command
{
    protected $signature = 'riesgo:recompute {--windows=30,60,120}';
    protected $description = 'Genera matches HECHOS vs WAZE (por cell_key) y recalcula zonas de riesgo';

    public function handle()
    {
        $tz = 'America/Mexico_City';
        $now = Carbon::now($tz);

        $windows = collect(explode(',', (string)$this->option('windows')))
            ->map(fn($x) => (int)trim($x))
            ->filter(fn($x) => $x > 0)
            ->values();

        if ($windows->isEmpty()) $windows = collect([30, 60, 120]);

        try {
            // 1) MATCHES: buscamos hechos recientes (ej: últimas 72 horas) con coords
            //    y dentro de su ventana buscamos: ACCIDENT antes y JAM después en la misma celda.
            $this->info('1) Generando matches...');
            $this->buildMatches($now);

            // 2) ZONAS: para cada ventana, sumar jams recientes por cell_key y hechos históricos por cell_key
            $this->info('2) Recalculando zonas de riesgo...');
            foreach ($windows as $w) {
                $this->recomputeZones($now, $w);
            }

            $this->info('OK: riesgo recalculado.');
            return 0;

        } catch (\Throwable $e) {
            Log::error('riesgo:recompute failed', ['error' => $e->getMessage()]);
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function buildMatches(Carbon $now): void
    {
        // Ventanas para determinar causalidad simple
        $beforeMin = 90;   // ACCIDENT puede estar hasta 90 min antes del hecho
        $afterMin  = 180;  // JAM puede estar hasta 180 min después del hecho

        // Tomamos hechos recientes para no recalcular el mundo
        // (ajusta a 7 días si quieres)
        $since = $now->copy()->subHours(72)->toDateTimeString();

        // Insert/Update con SQL para que sea rápido
        // Ojo: usamos cell_key ya precomputado
        $sql = "
            INSERT INTO waze_hecho_matches
            (
              hecho_id, waze_accident_id, waze_first_jam_id,
              cell_key, fecha, hora,
              hecho_at, waze_accident_at, waze_first_jam_at,
              min_accident_to_hecho, min_hecho_to_jam,
              calle_norm, street_norm,
              created_at, updated_at
            )
            SELECT
              h.id AS hecho_id,

              -- ACCIDENT antes del hecho (más cercano hacia atrás)
              (
                SELECT w1.id
                FROM waze_alerts w1
                WHERE w1.cell_key = h.cell_key
                  AND w1.type = 'ACCIDENT'
                  AND w1.published_at BETWEEN (TIMESTAMP(h.fecha, h.hora) - INTERVAL {$beforeMin} MINUTE)
                                        AND  TIMESTAMP(h.fecha, h.hora)
                ORDER BY w1.published_at DESC
                LIMIT 1
              ) AS waze_accident_id,

              -- primer JAM después del hecho
              (
                SELECT w2.id
                FROM waze_alerts w2
                WHERE w2.cell_key = h.cell_key
                  AND w2.type = 'JAM'
                  AND w2.published_at BETWEEN TIMESTAMP(h.fecha, h.hora)
                                        AND (TIMESTAMP(h.fecha, h.hora) + INTERVAL {$afterMin} MINUTE)
                ORDER BY w2.published_at ASC
                LIMIT 1
              ) AS waze_first_jam_id,

              h.cell_key,
              h.fecha,
              h.hora,
              TIMESTAMP(h.fecha, h.hora) AS hecho_at,

              (
                SELECT w1.published_at
                FROM waze_alerts w1
                WHERE w1.cell_key = h.cell_key
                  AND w1.type = 'ACCIDENT'
                  AND w1.published_at BETWEEN (TIMESTAMP(h.fecha, h.hora) - INTERVAL {$beforeMin} MINUTE)
                                        AND  TIMESTAMP(h.fecha, h.hora)
                ORDER BY w1.published_at DESC
                LIMIT 1
              ) AS waze_accident_at,

              (
                SELECT w2.published_at
                FROM waze_alerts w2
                WHERE w2.cell_key = h.cell_key
                  AND w2.type = 'JAM'
                  AND w2.published_at BETWEEN TIMESTAMP(h.fecha, h.hora)
                                        AND (TIMESTAMP(h.fecha, h.hora) + INTERVAL {$afterMin} MINUTE)
                ORDER BY w2.published_at ASC
                LIMIT 1
              ) AS waze_first_jam_at,

              -- minutos
              TIMESTAMPDIFF(
                MINUTE,
                (
                  SELECT w1.published_at
                  FROM waze_alerts w1
                  WHERE w1.cell_key = h.cell_key
                    AND w1.type = 'ACCIDENT'
                    AND w1.published_at BETWEEN (TIMESTAMP(h.fecha, h.hora) - INTERVAL {$beforeMin} MINUTE)
                                          AND  TIMESTAMP(h.fecha, h.hora)
                  ORDER BY w1.published_at DESC
                  LIMIT 1
                ),
                TIMESTAMP(h.fecha, h.hora)
              ) AS min_accident_to_hecho,

              TIMESTAMPDIFF(
                MINUTE,
                TIMESTAMP(h.fecha, h.hora),
                (
                  SELECT w2.published_at
                  FROM waze_alerts w2
                  WHERE w2.cell_key = h.cell_key
                    AND w2.type = 'JAM'
                    AND w2.published_at BETWEEN TIMESTAMP(h.fecha, h.hora)
                                          AND (TIMESTAMP(h.fecha, h.hora) + INTERVAL {$afterMin} MINUTE)
                  ORDER BY w2.published_at ASC
                  LIMIT 1
                )
              ) AS min_hecho_to_jam,

              h.calle_norm,
              (
                SELECT w3.street_norm
                FROM waze_alerts w3
                WHERE w3.cell_key = h.cell_key
                  AND w3.type = 'ACCIDENT'
                  AND w3.published_at BETWEEN (TIMESTAMP(h.fecha, h.hora) - INTERVAL {$beforeMin} MINUTE)
                                        AND  TIMESTAMP(h.fecha, h.hora)
                ORDER BY w3.published_at DESC
                LIMIT 1
              ) AS street_norm,

              NOW(), NOW()

            FROM hechos h
            WHERE h.cell_key IS NOT NULL
              AND h.cell_key != ''
              AND h.created_at >= ?
            HAVING waze_accident_id IS NOT NULL
               AND waze_first_jam_id IS NOT NULL

            ON DUPLICATE KEY UPDATE
              waze_accident_id = VALUES(waze_accident_id),
              waze_first_jam_id = VALUES(waze_first_jam_id),
              waze_accident_at = VALUES(waze_accident_at),
              waze_first_jam_at = VALUES(waze_first_jam_at),
              min_accident_to_hecho = VALUES(min_accident_to_hecho),
              min_hecho_to_jam = VALUES(min_hecho_to_jam),
              calle_norm = VALUES(calle_norm),
              street_norm = VALUES(street_norm),
              updated_at = NOW()
        ";

        DB::statement($sql, [$since]);
    }

    private function recomputeZones(Carbon $now, int $windowMin): void
    {
        // Score simple:
        // score = jams_window * (1 + hechos_hist/50)
        // Ajusta el divisor 50 a gusto.

        // Históricos por cell
        // (si queremos “últimos 12 meses”, cambiar aquí)
        $histSql = "
            SELECT cell_key, COUNT(*) AS hechos_hist
            FROM hechos
            WHERE cell_key IS NOT NULL AND cell_key != ''
            GROUP BY cell_key
        ";

        // Jams recientes por cell
        $jamsSql = "
            SELECT cell_key, COUNT(*) AS jams_window
            FROM waze_alerts
            WHERE type='JAM'
              AND cell_key IS NOT NULL AND cell_key != ''
              AND published_at >= (NOW() - INTERVAL {$windowMin} MINUTE)
            GROUP BY cell_key
        ";

        // Upsert a risk_zones (sin corredor por ahora)
        $sql = "
            INSERT INTO risk_zones
            (cell_key, corredor, window_min, hechos_hist, jams_window, score, calculated_at, created_at, updated_at)
            SELECT
              x.cell_key,
              NULL AS corredor,
              {$windowMin} AS window_min,
              x.hechos_hist,
              y.jams_window,
              ROUND((y.jams_window * (1 + (x.hechos_hist / 50))), 2) AS score,
              NOW(),
              NOW(),
              NOW()
            FROM ({$histSql}) x
            JOIN ({$jamsSql}) y
              ON y.cell_key = x.cell_key
            ON DUPLICATE KEY UPDATE
              hechos_hist = VALUES(hechos_hist),
              jams_window = VALUES(jams_window),
              score = VALUES(score),
              calculated_at = VALUES(calculated_at),
              updated_at = NOW()
        ";

        DB::statement($sql);
    }
}
