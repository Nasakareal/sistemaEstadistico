<?php

namespace App\Console\Commands;

use App\Mail\EstadoFuerzaDiarioMail;
use App\Services\Exports\EstadoFuerzaExcelService;
use App\Services\ParteNovedadesGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarEstadoFuerzaDiario extends Command
{
    protected $signature = 'estadofuerza:enviar-diario {--corte=}';
    protected $description = 'Genera el Excel Estado de Fuerza y lo envía por correo (diario 18:00).';

    public function handle(
        EstadoFuerzaExcelService $service,
        ParteNovedadesGenerator $parteGen
    ): int {
        $tz = 'America/Mexico_City';

        $corteOpt = $this->option('corte');
        $corte = $corteOpt
            ? Carbon::parse($corteOpt, $tz)
            : now($tz);

        $rutaExcel = $service->generar($corte);

        $fechaTexto = $corte->format('Y-m-d H:i:s') . " ($tz)";
        $fileNameExcel = 'estado_fuerza_' . $corte->format('Y-m-d_His') . '.xlsx';

        $fechaParte = $corte->copy()->format('Y-m-d');
        $rutaParte = $parteGen->generar($fechaParte);
        $fileNameParte = 'parte_novedades_' . $fechaParte . '.docx';

        $to = $this->parseEmails(env('ESTADO_FUERZA_MAIL_TO', ''));
        $cc = $this->parseEmails(env('ESTADO_FUERZA_MAIL_CC', ''));
        $bcc = $this->parseEmails(env('ESTADO_FUERZA_MAIL_BCC', ''));

        if (empty($to)) {
            $this->error('No hay destinatarios. Define ESTADO_FUERZA_MAIL_TO en el .env');
            $this->safeDelete($rutaExcel);
            $this->safeDelete($rutaParte);
            return self::FAILURE;
        }

        Mail::to($to)
            ->cc($cc)
            ->bcc($bcc)
            ->send(new EstadoFuerzaDiarioMail(
                $rutaExcel,
                $fileNameExcel,
                $fechaTexto,
                $rutaParte,
                $fileNameParte
            ));

        $this->info('Enviado OK: ' . implode(', ', $to));
        $this->info('Adjunto Excel: ' . $rutaExcel);
        $this->info('Adjunto Parte: ' . $rutaParte);

        $this->safeDelete($rutaExcel);
        $this->safeDelete($rutaParte);

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

    protected function safeDelete(?string $path): void
    {
        if (!$path) return;
        if (is_file($path) && file_exists($path)) {
            @unlink($path);
        }
    }
}
