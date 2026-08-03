<?php

namespace App\Console\Commands;

use App\Mail\FormatoInegiChoquesMail;
use App\Models\InegiChoquesEnvio;
use App\Services\Inegi\InegiChoquesExcelGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EnviarFormatoInegiChoques extends Command
{
    protected $signature = 'inegi:enviar-choques
        {--fecha= : Fecha del dia de choques a enviar (YYYY-MM-DD).}
        {--desde= : Fecha inicial del rango de choques a enviar (YYYY-MM-DD).}
        {--hasta= : Fecha final del rango de choques a enviar (YYYY-MM-DD).}
        {--mes-actual : Envia del dia 1 del mes actual hasta hoy menos dos dias.}
        {--mes-anterior : Envia todo el mes calendario anterior. Es el comportamiento por default.}
        {--motivo-reenvio= : Motivo que aparecera en el correo junto con una disculpa.}';
    protected $description = 'Genera el formato INEGI de choques y lo envia por correo en un solo adjunto.';

    public function handle(InegiChoquesExcelGenerator $generator): int
    {
        $tz = (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));

        $to = $this->parseEmails((string) config('services.inegi_choques.mail_to', ''));
        $cc = $this->parseEmails((string) config('services.inegi_choques.mail_cc', ''));
        $bcc = $this->parseEmails((string) config('services.inegi_choques.mail_bcc', ''));

        if (empty($to)) {
            $this->error('No hay destinatarios. Define INEGI_CHOQUES_MAIL_TO en el .env');
            return self::FAILURE;
        }

        [$desde, $hasta] = $this->rangoAEnviar($tz);

        if ($hasta->lessThan($desde)) {
            $this->warn('No hay rango para enviar con las opciones indicadas.');
            return self::SUCCESS;
        }

        $motivoReenvio = trim((string) $this->option('motivo-reenvio')) ?: null;

        $this->enviarRango($generator, $desde, $hasta, $to, $cc, $bcc, $motivoReenvio);

        $this->info('Envio INEGI completado.');

        return self::SUCCESS;
    }

    protected function rangoAEnviar(string $tz): array
    {
        if ($this->option('fecha')) {
            $fecha = Carbon::parse((string) $this->option('fecha'), $tz)->startOfDay();

            return [$fecha, $fecha->copy()];
        }

        if ($this->option('desde') || $this->option('hasta')) {
            if (!$this->option('desde') || !$this->option('hasta')) {
                throw new \InvalidArgumentException('Para enviar por rango debes indicar --desde y --hasta.');
            }

            $desde = Carbon::parse((string) $this->option('desde'), $tz)->startOfDay();
            $hasta = Carbon::parse((string) $this->option('hasta'), $tz)->startOfDay();

            if ($hasta->lessThan($desde)) {
                throw new \InvalidArgumentException('La fecha --hasta no puede ser anterior a --desde.');
            }

            return [$desde, $hasta];
        }

        if ($this->option('mes-actual')) {
            $desde = now($tz)->startOfMonth();
            $hasta = now($tz)->subDays(2)->startOfDay();

            return [$desde, $hasta];
        }

        $mesAnterior = now($tz)->subMonthNoOverflow();

        return [
            $mesAnterior->copy()->startOfMonth(),
            $mesAnterior->copy()->endOfMonth()->startOfDay(),
        ];
    }

    protected function enviarRango(
        InegiChoquesExcelGenerator $generator,
        Carbon $desde,
        Carbon $hasta,
        array $to,
        array $cc,
        array $bcc,
        ?string $motivoReenvio = null
    ): void
    {
        $adjunto = $generator->generarAdjuntoRango($desde, $hasta);

        $auditoria = InegiChoquesEnvio::query()->firstOrNew([
            'fecha_inicio' => $desde->toDateString(),
            'fecha_fin' => $hasta->toDateString(),
        ]);

        $auditoria->estado = 'procesando';
        $auditoria->intentos = (int) $auditoria->intentos + 1;
        $auditoria->destinatarios = [
            'to' => array_values($to),
            'cc' => array_values($cc),
            'bcc' => array_values($bcc),
        ];
        $auditoria->archivo_nombre = $adjunto['name'];
        $auditoria->archivo_sha256 = hash('sha256', $adjunto['contents']);
        $auditoria->total_registros = (int) $adjunto['total'];
        $auditoria->ultimo_error = null;
        $auditoria->save();

        try {
            Mail::to($to)
                ->cc($cc)
                ->bcc($bcc)
                ->send(new FormatoInegiChoquesMail(
                    $desde,
                    $adjunto['name'],
                    $adjunto['contents'],
                    (int) $adjunto['total'],
                    $hasta,
                    $motivoReenvio
                ));

            DB::transaction(function () use ($auditoria, $adjunto) {
                $auditoria->update([
                    'estado' => 'enviado',
                    'enviado_at' => now(),
                    'ultimo_error' => null,
                ]);

                DB::table('inegi_choques_envio_hechos')
                    ->where('envio_id', $auditoria->id)
                    ->delete();

                $rows = collect($adjunto['hecho_ids'] ?? [])
                    ->unique()
                    ->map(fn ($hechoId) => [
                        'envio_id' => $auditoria->id,
                        'hecho_id' => (int) $hechoId,
                    ])
                    ->values()
                    ->all();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('inegi_choques_envio_hechos')->insert($chunk);
                }
            });
        } catch (Throwable $e) {
            $auditoria->update([
                'estado' => 'fallido',
                'ultimo_error' => Str::limit($e->getMessage(), 60000, ''),
            ]);

            throw $e;
        }

        $this->info('Periodo reportado: ' . $desde->toDateString() . ' a ' . $hasta->toDateString());
        $this->info('Choques incluidos: ' . (int) $adjunto['total']);
        $this->info('Adjunto generado en memoria; no se guardo en disco.');
    }

    protected function parseEmails(string $list): array
    {
        $list = trim($list);
        if ($list === '') {
            return [];
        }

        return array_values(array_filter(array_map(function ($email) {
            $email = trim($email);
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        }, explode(',', $list))));
    }

}
