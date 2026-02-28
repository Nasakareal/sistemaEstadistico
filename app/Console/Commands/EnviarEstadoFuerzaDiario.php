<?php

namespace App\Console\Commands;

use App\Mail\EstadoFuerzaDiarioMail;
use App\Services\Exports\EstadoFuerzaExcelService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarEstadoFuerzaDiario extends Command
{
    protected $signature = 'estadofuerza:enviar-diario {--corte=}';
    protected $description = 'Genera el Excel Estado de Fuerza y lo envía por correo (diario 18:00).';

    public function handle(EstadoFuerzaExcelService $service): int
    {
        $tz = 'America/Mexico_City';

        $corteOpt = $this->option('corte');
        $corte = $corteOpt
            ? Carbon::parse($corteOpt, $tz)
            : now($tz);

        $ruta = $service->generar($corte);

        $fechaTexto = $corte->format('Y-m-d H:i:s') . " ($tz)";
        $fileName = 'estado_fuerza_' . $corte->format('Y-m-d_His') . '.xlsx';

        $to = $this->parseEmails(env('ESTADO_FUERZA_MAIL_TO', ''));
        $cc = $this->parseEmails(env('ESTADO_FUERZA_MAIL_CC', ''));
        $bcc = $this->parseEmails(env('ESTADO_FUERZA_MAIL_BCC', ''));

        if (empty($to)) {
            $this->error('No hay destinatarios. Define ESTADO_FUERZA_MAIL_TO en el .env');
            return self::FAILURE;
        }

        Mail::to($to)
            ->cc($cc)
            ->bcc($bcc)
            ->send(new EstadoFuerzaDiarioMail($ruta, $fileName, $fechaTexto));

        $this->info('Enviado OK: ' . implode(', ', $to));
        $this->info('Adjunto: ' . $ruta);

        return self::SUCCESS;
    }

    protected function parseEmails(string $list): array
    {
        $list = trim($list);
        if ($list === '') return [];

        return array_values(array_filter(array_map(function ($e) {
            $e = trim($e);
            return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
        }, explode(',', $list))));
    }
}
