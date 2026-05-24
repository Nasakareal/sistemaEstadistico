<?php

namespace App\Console\Commands;

use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppSendGuard;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EnviarTarjetaHechosWhatsApp extends Command
{
    private const UNIDAD_SINIESTROS_ID = 1;

    protected $signature = 'whatsapp:tarjeta-hechos {--to=} {--sin-template} {--force}';
    protected $description = 'Envía la tarjeta diaria de hechos por WhatsApp con corte de 18:00 a 18:00';

    public function handle(WhatsAppCloudService $whatsApp, WhatsAppSendGuard $sendGuard): int
    {
        $timezone = 'America/Mexico_City';
        $now = Carbon::now($timezone);
        $cutoffToday = Carbon::today($timezone)->setTime(18, 0, 0);

        $end = $now->greaterThanOrEqualTo($cutoffToday)
            ? $cutoffToday->copy()
            : $cutoffToday->copy()->subDay();

        $start = $end->copy()->subDay();

        $totales = $this->getTotales($start, $end);

        $firma = (string) config(
            'services.whatsapp.siniestros.firma',
            'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS LIC. JULIO ERNESTO BAUTISTA JIMÉNEZ'
        );

        $to = (string) (
            $this->option('to')
            ?: config('services.whatsapp.siniestros.tarjeta_hechos_to')
            ?: config('services.whatsapp.siniestros.to')
            ?: config('services.whatsapp.default_to')
        );
        $recipients = $this->recipients($to);

        $template = (string) config('services.whatsapp.siniestros.tarjeta_hechos_template', '');

        if (empty($recipients)) {
            $this->error('No hay número destino. Define WHATSAPP_SINIESTROS_TARJETA_HECHOS_TO o usa --to=');
            return self::FAILURE;
        }

        $mensaje = $this->buildMessage($end, $totales, $firma);
        $periodKey = $end->format('Y-m-d_H:i');
        $failures = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($recipients as $recipient) {
            if (!$this->option('force') && !$sendGuard->reserve('tarjeta-hechos', $periodKey, $recipient)) {
                $this->warn('Tarjeta ya enviada o en proceso para ' . $recipient . ' en el corte ' . $periodKey . '. Usa --force para reenviar.');
                $skipped++;
                continue;
            }

            try {
                if ($this->option('sin-template') || $template === '') {
                    $response = $whatsApp->sendText($recipient, $mensaje);
                } else {
                    $response = $whatsApp->sendTemplate($recipient, $template, [
                        $end->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'),
                        $this->pad($totales['choques']),
                        $this->pad($totales['atropellados']),
                        $this->pad($totales['volcadura']),
                        $this->pad($totales['salida_superficie']),
                        $this->pad($totales['subida_camellon']),
                        $this->pad($totales['caida_cuneta']),
                        $this->pad($totales['caida_motocicleta']),
                        $this->pad($totales['incidente']),
                        $this->pad($totales['reporte']),
                        $this->pad($totales['lesionados']),
                        $this->pad($totales['fallecidos']),
                        $this->pad($totales['resueltos']),
                        $this->pad($totales['pendientes']),
                        $this->pad($totales['turnados']),
                    ]);
                }

                Log::info('Respuesta WhatsApp tarjeta hechos', $response);

                $this->line('--- RESPUESTA META (' . $recipient . ') ---');
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                if (!($response['ok'] ?? false)) {
                    $this->error('Meta rechazó el envío para ' . $recipient . '.');
                    $sendGuard->release('tarjeta-hechos', $periodKey, $recipient);
                    $failures++;
                    continue;
                }

                $body = $response['body'] ?? [];
                $messageId = $body['messages'][0]['id'] ?? null;

                if (!$messageId) {
                    $this->error('Meta respondió sin message id para ' . $recipient . '.');
                    $sendGuard->release('tarjeta-hechos', $periodKey, $recipient);
                    $failures++;
                    continue;
                }

                $sendGuard->markSent('tarjeta-hechos', $periodKey, $recipient, $messageId);

                $this->info('Mensaje aceptado por Meta para ' . $recipient . '. ID: '.$messageId);
                $sent++;
            } catch (\Throwable $e) {
                $sendGuard->release('tarjeta-hechos', $periodKey, $recipient);

                Log::error('Error enviando tarjeta de hechos WhatsApp', [
                    'to' => $recipient,
                    'error' => $e->getMessage(),
                ]);

                $this->error('Error enviando a ' . $recipient . ': ' . $e->getMessage());
                $failures++;
            }
        }

        $this->line('--- MENSAJE ARMADO ---');
        $this->line($mensaje);

        if ($failures > 0) {
            $this->error("Tarjeta procesada con {$sent} enviado(s), {$skipped} omitido(s) y {$failures} error(es).");
            return self::FAILURE;
        }

        $this->info("Tarjeta enviada a {$sent} destinatario(s). Omitidos por duplicado: {$skipped}.");
        return self::SUCCESS;
    }

    protected function buildMessage(Carbon $end, array $totales, string $firma): string
    {
        $fechaTexto = mb_strtoupper($end->copy()->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y'), 'UTF-8');

        return "GUARDIA CIVIL.\n\n"
            ."COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL.\n\n"
            ."UNIDAD DE ATENCIÓN A SINIESTROS.\n\n"
            ."ASUNTO: Reporte de Accidentes del día {$fechaTexto}.\n\n"
            ."POR ESTE CONDUCTO ME PERMITO INFORMAR, LAS NOVEDADES EN RELACION A LOS SINIESTROS OCURRIDOS DURANTE LAS ÚLTIMAS 24 HRS. EN DIFERENTES PUNTOS DE LA CIUDAD.\n\n"
            ."- ".$this->pad($totales['choques'])." Choques\n"
            ."- ".$this->pad($totales['atropellados'])." Atropellados\n"
            ."- ".$this->pad($totales['volcadura'])." Volcadura\n"
            ."- ".$this->pad($totales['salida_superficie'])." Salida de la superficie de rodamiento.\n"
            ."- ".$this->pad($totales['subida_camellon'])." Subida a camellón\n"
            ."- ".$this->pad($totales['caida_cuneta'])." Caída a cuneta\n"
            ."- ".$this->pad($totales['caida_motocicleta'])." Caída de motocicleta\n"
            ."- ".$this->pad($totales['incidente'])." Incidente\n"
            ."- ".$this->pad($totales['reporte'])." Reporte\n"
            ."- ".$this->pad($totales['lesionados'])." Lesionados\n"
            ."- ".$this->pad($totales['fallecidos'])." Fallecidos\n"
            ."- ".$this->pad($totales['resueltos'])." Resueltos\n"
            ."- ".$this->pad($totales['pendientes'])." Pendientes\n"
            ."- ".$this->pad($totales['turnados'])." Turnado\n\n"
            ."PARA CONOCIMIENTO DE LA SUPERIORIDAD.\n\n"
            ."RESPETUOSAMENTE:\n"
            .$firma;
    }

    protected function getTotales(Carbon $start, Carbon $end): array
    {
        $hechos = $this->getHechosBaseQuery($start, $end)->get(['id', 'tipo_hecho', 'situacion']);

        $totales = [
            'choques' => 0,
            'atropellados' => 0,
            'volcadura' => 0,
            'salida_superficie' => 0,
            'subida_camellon' => 0,
            'caida_cuneta' => 0,
            'caida_motocicleta' => 0,
            'incidente' => 0,
            'reporte' => 0,
            'lesionados' => 0,
            'fallecidos' => 0,
            'resueltos' => 0,
            'pendientes' => 0,
            'turnados' => 0,
        ];

        foreach ($hechos as $hecho) {
            $tipo = mb_strtoupper(trim((string) $hecho->tipo_hecho), 'UTF-8');
            $situacion = mb_strtoupper(trim((string) $hecho->situacion), 'UTF-8');

            if (str_contains($tipo, 'PEATÓN') || str_contains($tipo, 'PEATON') || str_contains($tipo, 'ATROPELL')) {
                $totales['atropellados']++;
            } elseif (str_contains($tipo, 'VOLCAD')) {
                $totales['volcadura']++;
            } elseif (str_contains($tipo, 'SALIDA DE LA SUPERFICIE DE RODAMIENTO') || str_contains($tipo, 'SALIDA DE RODAMIENTO')) {
                $totales['salida_superficie']++;
            } elseif (str_contains($tipo, 'CAMELLÓN') || str_contains($tipo, 'CAMELLON')) {
                $totales['subida_camellon']++;
            } elseif (str_contains($tipo, 'CUNETA')) {
                $totales['caida_cuneta']++;
            } elseif (str_contains($tipo, 'MOTOCICLETA')) {
                $totales['caida_motocicleta']++;
            } elseif (str_contains($tipo, 'INCIDENTE')) {
                $totales['incidente']++;
            } elseif (str_contains($tipo, 'REPORTE')) {
                $totales['reporte']++;
            } else {
                $totales['choques']++;
            }

            if ($situacion === 'RESUELTO' || $situacion === 'RESUELTA' || $situacion === 'REPORTE') {
                $totales['resueltos']++;
            } elseif ($situacion === 'TURNADO' || $situacion === 'TURNADA') {
                $totales['turnados']++;
            } elseif ($situacion === 'PENDIENTE' || $situacion === 'PENDIENTES') {
                $totales['pendientes']++;
            }
        }

        $totales['lesionados'] = $this->getTotalLesionados($start, $end);
        $totales['fallecidos'] = $this->getTotalFallecidos($start, $end);

        return $totales;
    }

    protected function getHechosBaseQuery(Carbon $start, Carbon $end)
    {
        $query = DB::table('hechos')
            ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID);

        if (Schema::hasColumn('hechos', 'fecha') && Schema::hasColumn('hechos', 'hora')) {
            return $query->whereRaw(
                "TIMESTAMP(fecha, COALESCE(NULLIF(hora, ''), '00:00:00')) >= ? AND TIMESTAMP(fecha, COALESCE(NULLIF(hora, ''), '00:00:00')) < ?",
                [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
            );
        }

        return $query->whereBetween('created_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
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
