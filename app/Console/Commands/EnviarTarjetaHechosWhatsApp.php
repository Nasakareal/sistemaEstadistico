<?php

namespace App\Console\Commands;

use App\Services\WhatsAppCloudService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EnviarTarjetaHechosWhatsApp extends Command
{
    protected $signature = 'whatsapp:tarjeta-hechos {--to=} {--sin-template}';
    protected $description = 'Envía la tarjeta diaria de hechos por WhatsApp con corte de 18:00 a 18:00';

    public function handle(WhatsAppCloudService $whatsApp): int
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

        $template = (string) config('services.whatsapp.siniestros.tarjeta_hechos_template', '');

        if ($to === '') {
            $this->error('No hay número destino. Define WHATSAPP_SINIESTROS_TARJETA_HECHOS_TO o usa --to=');
            return self::FAILURE;
        }

        $mensaje = $this->buildMessage($end, $totales, $firma);

        try {
            if ($this->option('sin-template') || $template === '') {
                $response = $whatsApp->sendText($to, $mensaje);
            } else {
                $response = $whatsApp->sendTemplate($to, $template, [
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

            $this->line('--- RESPUESTA META ---');
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line('--- MENSAJE ARMADO ---');
            $this->line($mensaje);

            if (!($response['ok'] ?? false)) {
                $this->error('Meta rechazó el envío.');
                return self::FAILURE;
            }

            $body = $response['body'] ?? [];
            $messageId = $body['messages'][0]['id'] ?? null;

            if (!$messageId) {
                $this->error('Meta respondió sin message id.');
                return self::FAILURE;
            }

            $this->info('Mensaje aceptado por Meta. ID: '.$messageId);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Error enviando tarjeta de hechos WhatsApp', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            $this->error($e->getMessage());
            return self::FAILURE;
        }
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

            if ($situacion === 'RESUELTO' || $situacion === 'RESUELTA') {
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
        $query = DB::table('hechos');

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
            ->join('hechos', 'hechos.id', '=', 'lesionados.hecho_id');

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

        $hechoColumns = Schema::getColumnListing('hechos');
        $sumColumns = ['fallecidos', 'num_fallecidos', 'numero_fallecidos', 'total_fallecidos', 'cantidad_fallecidos'];

        foreach ($sumColumns as $column) {
            if (in_array($column, $hechoColumns, true)) {
                $query = DB::table('hechos');

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

    protected function pad(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }
}
