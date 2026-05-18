<?php

namespace App\Console\Commands;

use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EnviarResumenSiniesrosWhatsApp extends Command
{
    private const UNIDAD_SINIESTROS_ID = 1;

    protected $signature = 'whatsapp:resumen-siniestros {--to=} {--sin-template} {--force}';
    protected $description = 'Envía el resumen diario de siniestros por WhatsApp';

    public function handle(WhatsAppCloudService $whatsApp, WhatsAppSendGuard $sendGuard): int
    {
        $timezone = 'America/Mexico_City';
        $now = Carbon::now($timezone);
        $cutoffToday = Carbon::today($timezone)->setTime(18, 0, 0);

        $end = $now->greaterThanOrEqualTo($cutoffToday)
            ? $cutoffToday->copy()
            : $cutoffToday->copy()->subDay();

        $start = $end->copy()->subDay();

        $totalHechos = $this->getTotalHechos($start, $end);
        $totalLesionados = $this->getTotalLesionados($start, $end);
        $totalFallecidos = $this->getTotalFallecidos($start, $end);

        $fechaTexto = mb_strtoupper($end->copy()->locale('es')->translatedFormat('l d/m/Y'), 'UTF-8');
        $horaTexto = $end->format('H:i');

        $firma = (string) config(
            'services.whatsapp.siniestros.firma',
            'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS LIC. JULIO ERNESTO BAUTISTA JIMÉNEZ'
        );

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.siniestros.resumen_to')
            ?: config('services.whatsapp.siniestros.to')
            ?: config('services.whatsapp.default_to')
        );
        $recipients = $this->recipients($to);

        $template = (string) config('services.whatsapp.siniestros.resumen_template', '');

        if (empty($recipients)) {
            $this->error('No hay número destino. Define WHATSAPP_SINIESTROS_RESUMEN_TO o usa --to=');
            return self::FAILURE;
        }

        $mensaje = $this->buildMessage(
            $fechaTexto,
            $horaTexto,
            $totalHechos,
            $totalLesionados,
            $totalFallecidos,
            $firma
        );

        $periodKey = $end->format('Y-m-d_H:i');
        $failures = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($recipients as $to) {
            if (!$this->option('force') && !$sendGuard->reserve('resumen-siniestros', $periodKey, $to)) {
                $this->warn('Resumen ya enviado o en proceso para ' . $to . ' en el corte ' . $periodKey . '. Usa --force para reenviar.');
                $skipped++;
                continue;
            }

            try {
                if ($this->option('sin-template') || $template === '') {
                    $response = $whatsApp->sendText($to, $mensaje);
                } else {
                    $response = $whatsApp->sendTemplate($to, $template, [
                        $fechaTexto,
                        $horaTexto,
                        $this->pad($totalHechos),
                        $this->pad($totalLesionados),
                        $this->pad($totalFallecidos),
                    ]);
                }

                Log::info('Respuesta WhatsApp resumen siniestros', $response);

                $this->line('--- RESPUESTA META (' . $to . ') ---');
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                if (!($response['ok'] ?? false)) {
                    $this->error('Meta rechazó el envío para ' . $to . '.');
                    $sendGuard->release('resumen-siniestros', $periodKey, $to);
                    $failures++;
                    continue;
                }

                $body = $response['body'] ?? [];
                $messageId = $body['messages'][0]['id'] ?? null;

                if (!$messageId) {
                    $this->error('Meta respondió sin message id para ' . $to . '.');
                    $sendGuard->release('resumen-siniestros', $periodKey, $to);
                    $failures++;
                    continue;
                }

                $sendGuard->markSent('resumen-siniestros', $periodKey, $to, $messageId);

                $this->info('Mensaje aceptado por Meta para ' . $to . '. ID: ' . $messageId);
                $sent++;
            } catch (\Throwable $e) {
                $sendGuard->release('resumen-siniestros', $periodKey, $to);

                Log::error('Error enviando resumen WhatsApp', [
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);

                $this->error('Error enviando a ' . $to . ': ' . $e->getMessage());
                $failures++;
            }
        }

        $this->line('--- MENSAJE ARMADO ---');
        $this->line($mensaje);

        if ($failures > 0) {
            $this->error("Resumen procesado con {$sent} enviado(s), {$skipped} omitido(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Resumen enviado a {$sent} destinatario(s). Omitidos por duplicado: {$skipped}.");
        return self::SUCCESS;
    }

    protected function buildMessage(string $fechaTexto, string $horaTexto, int $hechos, int $lesionados, int $fallecidos, string $firma): string
    {
        return "GUARDIA CIVIL.\n\n"
            ."COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL.\n\n"
            ."UNIDAD DE ATENCIÓN A SINIESTROS.\n\n"
            ."ASUNTO: NOVEDADES {$fechaTexto}\n"
            ."{$horaTexto} HRS.\n\n"
            ."POR ESTE CONDUCTO ME PERMITO INFORMAR, LAS NOVEDADES DE LOS HECHOS DE TRÁNSITO OCURRIDOS DURANTE LAS ÚLTIMAS 24 HRS.\n\n"
            ."TOTAL: ".$this->pad($hechos)." HECHOS DE TRÁNSITO.\n\n"
            .$this->pad($lesionados)." LESIONADOS\n"
            .$this->pad($fallecidos)." FALLECIDOS\n\n"
            ."PARA CONOCIMIENTO DE LA SUPERIORIDAD.\n\n"
            ."RESPETUOSAMENTE:\n"
            .$firma;
    }

    protected function getTotalHechos(Carbon $start, Carbon $end): int
    {
        $query = DB::table('hechos')
            ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID);

        if (Schema::hasColumn('hechos', 'fecha') && Schema::hasColumn('hechos', 'hora')) {
            return (int) $query
                ->whereRaw(
                    "TIMESTAMP(fecha, COALESCE(NULLIF(hora, ''), '00:00:00')) >= ? AND TIMESTAMP(fecha, COALESCE(NULLIF(hora, ''), '00:00:00')) < ?",
                    [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
                )
                ->count();
        }

        return (int) $query
            ->whereBetween('created_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
            ->count();
    }

    protected function getTotalLesionados(Carbon $start, Carbon $end): int
    {
        if (!Schema::hasTable('lesionados')) {
            return 0;
        }

        $query = DB::table('lesionados')
            ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id')
            ->where('hechos.unidad_org_id', self::UNIDAD_SINIESTROS_ID);

        $this->whereLesionadoNoFallecido($query);

        if (Schema::hasColumn('hechos', 'fecha') && Schema::hasColumn('hechos', 'hora')) {
            return (int) $query
                ->whereRaw(
                    "TIMESTAMP(hechos.fecha, COALESCE(NULLIF(hechos.hora, ''), '00:00:00')) >= ? AND TIMESTAMP(hechos.fecha, COALESCE(NULLIF(hechos.hora, ''), '00:00:00')) < ?",
                    [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
                )
                ->count('lesionados.id');
        }

        return (int) $query
            ->whereBetween('hechos.created_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
            ->count('lesionados.id');
    }

    protected function getTotalFallecidos(Carbon $start, Carbon $end): int
    {
        if (!Schema::hasTable('hechos')) {
            return 0;
        }

        if (Schema::hasTable('lesionados')) {
            $query = DB::table('lesionados')
                ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id')
                ->where('hechos.unidad_org_id', self::UNIDAD_SINIESTROS_ID);

            $this->whereLesionadoFallecido($query);

            if (Schema::hasColumn('hechos', 'fecha') && Schema::hasColumn('hechos', 'hora')) {
                return (int) $query
                    ->whereRaw(
                        "TIMESTAMP(hechos.fecha, COALESCE(NULLIF(hechos.hora, ''), '00:00:00')) >= ? AND TIMESTAMP(hechos.fecha, COALESCE(NULLIF(hechos.hora, ''), '00:00:00')) < ?",
                        [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
                    )
                    ->count('lesionados.id');
            }

            return (int) $query
                ->whereBetween('hechos.created_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                ->count('lesionados.id');
        }

        $hechoColumns = Schema::getColumnListing('hechos');
        $sumColumns = ['fallecidos', 'num_fallecidos', 'numero_fallecidos', 'total_fallecidos', 'cantidad_fallecidos'];

        foreach ($sumColumns as $column) {
            if (in_array($column, $hechoColumns, true)) {
                $query = DB::table('hechos')
                    ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID);

                if (Schema::hasColumn('hechos', 'fecha') && Schema::hasColumn('hechos', 'hora')) {
                    return (int) $query
                        ->whereRaw(
                            "TIMESTAMP(fecha, COALESCE(NULLIF(hora, ''), '00:00:00')) >= ? AND TIMESTAMP(fecha, COALESCE(NULLIF(hora, ''), '00:00:00')) < ?",
                            [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
                        )
                        ->sum($column);
                }

                return (int) $query
                    ->whereBetween('created_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
                    ->sum($column);
            }
        }

        return 0;
    }

    protected function whereLesionadoNoFallecido($query): void
    {
        $query->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) <> 'FALLECIDO'");
    }

    protected function whereLesionadoFallecido($query): void
    {
        $query->whereRaw("UPPER(TRIM(COALESCE(lesionados.tipo_lesion, ''))) = 'FALLECIDO'");
    }

    protected function recipients(string $configured): array
    {
        $parts = preg_split('/[\s,;]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
        $numbers = [];

        foreach ($parts ?: [] as $part) {
            $number = preg_replace('/\D+/', '', (string) $part);

            if ($number !== '') {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }

    protected function pad(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }
}
