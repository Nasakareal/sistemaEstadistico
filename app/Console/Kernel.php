<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('hechos:notificar-pendientes')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('hechos:corte-pendientes')
            ->sundays()
            ->at('18:05')
            ->withoutOverlapping();

        $schedule->command('hechos:reporte-pendientes --json')
            ->sundays()
            ->at('18:06')
            ->withoutOverlapping();

        $schedule->command('waze:fetch-alerts')
            ->everyTwoMinutes()
            ->withoutOverlapping();

        $schedule->command('estadofuerza:enviar-diario')
            ->timezone('America/Mexico_City')
            ->dailyAt('18:00')
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-parte-novedades-diario')
            ->timezone('America/Mexico_City')
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-bitacora-diaria')
            ->timezone('America/Mexico_City')
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-mini-parte-diario')
            ->timezone('America/Mexico_City')
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('siniestros:generar-actividad-informe-diario')
            ->timezone('America/Mexico_City')
            ->dailyAt(substr(config('cortes.hora_corte', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('whatsapp:resumen-siniestros')
            ->dailyAt('18:00')
            ->timezone('America/Mexico_City')
            ->withoutOverlapping();

        $schedule->command('whatsapp:tarjeta-hechos')
            ->dailyAt('18:01')
            ->timezone('America/Mexico_City')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
