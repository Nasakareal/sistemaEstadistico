<?php

namespace App\Console\Commands;

use App\Mail\AlcoholimetriaMensualMail;
use App\Models\AlcoholimetriaReporteMensual;
use App\Services\Alcoholimetria\AlcoholimetriaMensualDocxGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EnviarAlcoholimetriaMensual extends Command
{
    protected $signature = 'alcoholimetria:enviar-reporte-mensual
        {--mes= : Mes a reportar en formato YYYY-MM; por defecto usa el mes anterior.}
        {--solo-generar : Genera el DOCX sin enviar correo ni registrar el envío.}
        {--salida= : Ruta completa del DOCX cuando se usa --solo-generar.}
        {--forzar : Vuelve a enviar aunque el mes ya figure como enviado.}';

    protected $description = 'Genera y envía el concentrado mensual de alcoholimetría con reintentos diarios.';

    public function handle(AlcoholimetriaMensualDocxGenerator $generator): int
    {
        try {
            $mes = $this->mesAReportar();
            $mesInicial = $this->mesInicial();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($mes->lt($mesInicial)) {
            $this->info(sprintf(
                'No se genera %s: los reportes de alcoholimetría comienzan en %s.',
                $mes->format('Y-m'),
                $mesInicial->format('Y-m')
            ));

            return self::SUCCESS;
        }

        $salida = $this->option('salida')
            ? (string) $this->option('salida')
            : null;

        if ($this->option('solo-generar')) {
            $reporte = $generator->generar($mes, $salida);
            $this->info('Reporte generado: ' . $reporte['path']);
            $this->mostrarResumen($reporte['resumen']);

            return self::SUCCESS;
        }

        $destinatarios = $this->destinatarios();
        if (empty($destinatarios)) {
            $this->error('No hay destinatarios válidos para el reporte mensual de alcoholimetría.');

            return self::FAILURE;
        }

        $auditoria = AlcoholimetriaReporteMensual::query()->firstOrNew([
            'mes' => $mes->toDateString(),
        ]);

        if ($auditoria->exists && $auditoria->estado === 'enviado' && !$this->option('forzar')) {
            $this->info('El reporte de ' . $mes->format('Y-m') . ' ya fue enviado.');

            return self::SUCCESS;
        }

        $auditoria->estado = 'procesando';
        $auditoria->intentos = (int) $auditoria->intentos + 1;
        $auditoria->destinatarios = $destinatarios;
        $auditoria->ultimo_error = null;
        $auditoria->save();

        $reporte = null;

        try {
            $reporte = $generator->generar($mes);

            Mail::to($destinatarios)->send(new AlcoholimetriaMensualMail(
                $mes,
                $reporte['name'],
                $reporte['contents'],
                $reporte['resumen']
            ));

            $auditoria->update([
                'estado' => 'enviado',
                'archivo_nombre' => $reporte['name'],
                'archivo_sha256' => $reporte['sha256'],
                'resumen' => $reporte['resumen'],
                'enviado_at' => now(),
                'ultimo_error' => null,
            ]);

            $this->info('Reporte enviado a: ' . implode(', ', $destinatarios));
            $this->mostrarResumen($reporte['resumen']);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $auditoria->update([
                'estado' => 'fallido',
                'ultimo_error' => Str::limit($e->getMessage(), 60000, ''),
            ]);

            report($e);
            $this->error('No se pudo enviar el reporte: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            if ($reporte && !empty($reporte['path']) && is_file($reporte['path'])) {
                @unlink($reporte['path']);
            }
        }
    }

    private function mesAReportar(): Carbon
    {
        $tz = (string) config(
            'app.schedule_timezone',
            config('app.timezone', 'America/Mexico_City')
        );
        $opcion = trim((string) $this->option('mes'));

        if ($opcion === '') {
            return now($tz)->subMonthNoOverflow()->startOfMonth();
        }

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $opcion)) {
            throw new \InvalidArgumentException('La opción --mes debe usar el formato YYYY-MM.');
        }

        return Carbon::createFromFormat('Y-m-d', $opcion . '-01', $tz)->startOfMonth();
    }

    private function mesInicial(): Carbon
    {
        $configurado = trim((string) config(
            'services.alcoholimetria_mensual.start_month',
            '2026-07'
        ));

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $configurado)) {
            throw new \InvalidArgumentException(
                'ALCOHOLIMETRIA_MENSUAL_START_MONTH debe usar el formato YYYY-MM.'
            );
        }

        return Carbon::createFromFormat('Y-m-d', $configurado . '-01')->startOfMonth();
    }

    private function destinatarios(): array
    {
        $obligatorios = (array) config(
            'services.alcoholimetria_mensual.required_mail_to',
            []
        );
        $adicionales = explode(',', (string) config(
            'services.alcoholimetria_mensual.mail_to',
            ''
        ));

        return collect(array_merge($obligatorios, $adicionales))
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function mostrarResumen(array $resumen): void
    {
        $this->line('Mes: ' . Carbon::parse($resumen['mes'])->format('Y-m'));
        $this->line('Pruebas reales: ' . $resumen['pruebas_reales']);
        $this->line('Boquillas perdidas: ' . $resumen['boquillas']['perdidas']);
        $this->line('Total conciliado: ' . $resumen['pruebas_reportadas']);
    }
}
