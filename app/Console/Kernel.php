<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $timezone = (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));

        $schedule->command('hechos:notificar-pendientes')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('hechos:corte-pendientes')
            ->timezone($timezone)
            ->sundays()
            ->at('18:05')
            ->withoutOverlapping();

        $schedule->command('hechos:generar-corte-pendientes --json')
            ->timezone($timezone)
            ->sundays()
            ->at('18:06')
            ->withoutOverlapping();

        $schedule->command('waze:fetch-alerts')
            ->everyTwoMinutes()
            ->withoutOverlapping();

        $schedule->command('estadofuerza:enviar-diario')
            ->timezone($timezone)
            ->dailyAt('18:00')
            ->withoutOverlapping();

        $schedule->command('inegi:enviar-choques')
            ->timezone($timezone)
            ->monthlyOn(1, (string) config('services.inegi_choques.schedule_time', '04:30'))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-parte-novedades-diario')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-bitacora-diaria')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-mini-parte-diario')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-actividad-informe-diario')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('actividades:fotos-migrar-blob')
            ->timezone($timezone)
            ->dailyAt('05:00')
            ->withoutOverlapping();

        $schedule->command('delegaciones:generar-excel-diario')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte_delegaciones', '17:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('fomento:generar-excel-diario')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte_fomento', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('vialidades-urbanas:generar-excel-diario')
            ->timezone($timezone)
            ->dailyAt(substr(config('cortes.hora_corte_vialidades_urbanas', '17:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('delegaciones:notificar-hechos-incompletos')
            ->timezone($timezone)
            ->hourly()
            ->withoutOverlapping();

        foreach ((array) config('services.whatsapp.delegaciones.cortes_schedule_times', ['15:00', '20:00', '22:00']) as $corteDelegaciones) {
            $corteDelegaciones = trim((string) $corteDelegaciones);

            if (!preg_match('/^\d{1,2}:\d{2}$/', $corteDelegaciones)) {
                continue;
            }

            $schedule->command('whatsapp:delegaciones-corte-aseguramientos')
                ->timezone($timezone)
                ->dailyAt($corteDelegaciones)
                ->withoutOverlapping();
        }

        $schedule->command('personal:licencias-vencidas-whatsapp')
            ->timezone($timezone)
            ->dailyAt('08:00')
            ->withoutOverlapping();

        $schedule->command('licencias-puntos:recuperar')
            ->timezone($timezone)
            ->dailyAt('02:30')
            ->withoutOverlapping();

        $schedule->command('whatsapp:resumen-siniestros')
            ->dailyAt('18:03')
            ->timezone($timezone);

        $schedule->command('whatsapp:tarjeta-hechos')
            ->dailyAt('18:08')
            ->timezone($timezone);

        $schedule->command('whatsapp:actividades-siniestros --regenerar')
            ->dailyAt('18:15')
            ->timezone($timezone);

        $schedule->command('whatsapp:vialidades-urbanas-diario')
            ->dailyAt(substr(config('cortes.hora_corte_vialidades_urbanas', '17:00:00'), 0, 5))
            ->timezone($timezone)
            ->withoutOverlapping();

        $schedule->command('whatsapp:resumen-todas-unidades')
            ->dailyAt('19:00')
            ->timezone($timezone)
            ->withoutOverlapping();

        $schedule->command('delegaciones:generar-excel-mensual')
            ->timezone($timezone)
            ->monthlyOn(1, '00:10')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
