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
        {--fecha= : Fecha del dia de choques a enviar (YYYY-MM-DD). Por default, dos dias antes.}
        {--desde= : Fecha inicial del rango de choques a enviar (YYYY-MM-DD).}
        {--hasta= : Fecha final del rango de choques a enviar (YYYY-MM-DD).}
        {--mes-actual : Envia del dia 1 del mes actual hasta hoy menos dos dias.}';
    protected $description = 'Genera el formato INEGI de choques y lo envia por correo.';

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

        $fechas = $this->fechasAEnviar($tz);
        if (empty($fechas)) {
            $this->warn('No hay fechas para enviar con las opciones indicadas.');
            return self::SUCCESS;
        }

        foreach ($fechas as $fecha) {
            $this->enviarFecha($generator, $fecha, $to, $cc, $bcc);
        }

        $this->info('Envios INEGI completados: ' . count($fechas));

        return self::SUCCESS;
    }

    protected function fechasAEnviar(string $tz): array
    {
        if ($this->option('mes-actual')) {
            $desde = now($tz)->startOfMonth();
            $hasta = now($tz)->subDays(2)->startOfDay();

            if ($hasta->lessThan($desde)) {
                return [];
            }

            return $this->rangoFechas($desde, $hasta);
        }

        if ($this->option('desde') || $this->option('hasta')) {
            if (!$this->option('desde') || !$this->option('hasta')) {
                throw new \InvalidArgumentException('Para enviar por rango debes indicar --desde y --hasta.');
            }

            return $this->rangoFechas(
                Carbon::parse((string) $this->option('desde'), $tz)->startOfDay(),
                Carbon::parse((string) $this->option('hasta'), $tz)->startOfDay()
            );
        }

        return [
            $this->option('fecha')
                ? Carbon::parse((string) $this->option('fecha'), $tz)->startOfDay()
                : now($tz)->subDays(2)->startOfDay(),
        ];
    }

    protected function rangoFechas(Carbon $desde, Carbon $hasta): array
    {
        if ($hasta->lessThan($desde)) {
            throw new \InvalidArgumentException('La fecha --hasta no puede ser anterior a --desde.');
        }

        $fechas = [];
        $cursor = $desde->copy();

        while ($cursor->lessThanOrEqualTo($hasta)) {
            $fechas[] = $cursor->copy();
            $cursor->addDay();
        }

        return $fechas;
    }

    protected function enviarFecha(InegiChoquesExcelGenerator $generator, Carbon $fecha, array $to, array $cc, array $bcc): void
    {
        $adjunto = $generator->generarAdjunto($fecha);

        Mail::to($to)
            ->cc($cc)
            ->bcc($bcc)
            ->send(new FormatoInegiChoquesMail(
                $fecha,
                $adjunto['name'],
                $adjunto['contents'],
                (int) $adjunto['total']
            ));

        $this->info('Fecha reportada: ' . $fecha->toDateString());
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
