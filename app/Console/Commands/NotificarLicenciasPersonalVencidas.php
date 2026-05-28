<?php

namespace App\Console\Commands;

use App\Services\PersonalLicenciaWhatsAppAlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotificarLicenciasPersonalVencidas extends Command
{
    protected $signature = 'personal:licencias-vencidas-whatsapp
        {--dry-run : Muestra las licencias vencidas sin enviar WhatsApp}
        {--force : Incluye licencias ya notificadas}
        {--to= : Sobrescribe destinatarios separados por coma}';

    protected $description = 'Notifica por WhatsApp las licencias vencidas registradas en personal';

    public function handle(PersonalLicenciaWhatsAppAlertService $alertas): int
    {
        $fecha = Carbon::now('America/Mexico_City');
        $force = (bool) $this->option('force');
        $licencias = $alertas->licenciasVencidasPendientes($fecha, $force);

        if ($licencias->isEmpty()) {
            $this->info('No hay licencias vencidas pendientes de alertar.');

            return self::SUCCESS;
        }

        $destinatarios = $alertas->destinatarios($this->option('to') ?: null);

        if ($this->option('dry-run')) {
            $this->table(
                ['Personal', 'Tipo', 'Numero', 'Vigencia', 'Unidad', 'Ya notificada'],
                $licencias->map(function ($licencia) {
                    $personal = $licencia->personal;
                    $nombre = $personal ? $personal->nombre_completo : '';

                    return [
                        $nombre !== '' ? $nombre : ('Personal #' . $licencia->personal_id),
                        $licencia->tipo_label,
                        $licencia->numero ?: 'Sin numero',
                        optional($licencia->vigencia)->format('Y-m-d'),
                        optional($personal ? $personal->unidad : null)->nombre ?: 'Sin unidad',
                        $licencia->vencimiento_notificado_at
                            ? $licencia->vencimiento_notificado_at->format('Y-m-d H:i')
                            : 'No',
                    ];
                })->all()
            );

            $this->line('Destinatarios: ' . (implode(', ', $destinatarios) ?: 'Sin destinatarios'));
            $this->line('--- MENSAJE ---');
            $this->line($alertas->mensaje($licencias, $fecha));
            $this->info('Dry-run: no se envio WhatsApp. Candidatos: ' . $licencias->count());

            return self::SUCCESS;
        }

        if (empty($destinatarios)) {
            $this->error('No hay destinatarios. Define WHATSAPP_OFICIOS_TERMINOS_TO o usa --to=');

            return self::FAILURE;
        }

        $resultado = $alertas->notificar($licencias, $destinatarios, $fecha);

        if ((int) $resultado['mensajes_fallidos'] > 0) {
            $this->error("Licencias: {$resultado['licencias']}. Mensajes enviados: {$resultado['mensajes_enviados']}. Fallidos: {$resultado['mensajes_fallidos']}.");

            return self::FAILURE;
        }

        $this->info("Licencias alertadas: {$resultado['licencias']}. Destinatarios: {$resultado['destinatarios_con_envio']}/{$resultado['destinatarios']}.");

        return self::SUCCESS;
    }
}
