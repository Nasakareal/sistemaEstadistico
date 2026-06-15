<?php

namespace App\Console\Commands;

use App\Mail\FormatoInegiChoquesMail;
use App\Services\Inegi\InegiChoquesExcelGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarFormatoInegiChoques extends Command
{
    protected $signature = 'inegi:enviar-choques
        {--fecha= : Fecha del dia de choques a enviar (YYYY-MM-DD).}
        {--desde= : Fecha inicial del rango de choques a enviar (YYYY-MM-DD).}
        {--hasta= : Fecha final del rango de choques a enviar (YYYY-MM-DD).}
        {--mes-actual : Envia del dia 1 del mes actual hasta hoy menos dos dias.}
        {--mes-anterior : Envia todo el mes calendario anterior. Es el comportamiento por default.}';
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

        $this->enviarRango($generator, $desde, $hasta, $to, $cc, $bcc);

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

    protected function enviarRango(InegiChoquesExcelGenerator $generator, Carbon $desde, Carbon $hasta, array $to, array $cc, array $bcc): void
    {
        $adjunto = $generator->generarAdjuntoRango($desde, $hasta);

        Mail::to($to)
            ->cc($cc)
            ->bcc($bcc)
            ->send(new FormatoInegiChoquesMail(
                $desde,
                $adjunto['name'],
                $adjunto['contents'],
                (int) $adjunto['total'],
                $hasta
            ));

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
