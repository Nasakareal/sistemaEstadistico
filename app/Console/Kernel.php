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
            ->timezone('America/Mexico_City')
            ->sundays()
            ->at('18:05')
            ->withoutOverlapping();

        $schedule->command('hechos:generar-corte-pendientes --json')
            ->timezone('America/Mexico_City')
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

        $schedule->command('actividades:depurar-fotos')
            ->timezone('America/Mexico_City')
            ->dailyAt('02:30')
            ->withoutOverlapping();

        $schedule->command('delegaciones:generar-excel-diario')
            ->timezone('America/Mexico_City')
            ->dailyAt(substr(config('cortes.hora_corte_delegaciones', '17:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('fomento:generar-excel-diario')
            ->timezone('America/Mexico_City')
            ->dailyAt(substr(config('cortes.hora_corte_fomento', '18:00:00'), 0, 5))
            ->withoutOverlapping();

        $schedule->command('delegaciones:notificar-hechos-incompletos')
            ->timezone('America/Mexico_City')
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('personal:licencias-vencidas-whatsapp')
            ->timezone('America/Mexico_City')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        $schedule->command('whatsapp:resumen-siniestros')
            ->dailyAt('18:00')
            ->timezone('America/Mexico_City')
            ->withoutOverlapping();

        $schedule->command('whatsapp:tarjeta-hechos')
            ->dailyAt('18:01')
            ->timezone('America/Mexico_City')
            ->withoutOverlapping();

        $schedule->command('whatsapp:actividades-siniestros --regenerar')
            ->dailyAt('18:05')
            ->timezone('America/Mexico_City')
            ->withoutOverlapping();

        $schedule->command('whatsapp:resumen-todas-unidades')
            ->dailyAt('19:00')
            ->timezone('America/Mexico_City')
            ->withoutOverlapping();

        $schedule->command('delegaciones:generar-excel-mensual')
            ->timezone('America/Mexico_City')
            ->monthlyOn(1, '00:10')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
