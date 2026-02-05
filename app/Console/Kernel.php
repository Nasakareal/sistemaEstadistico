<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('users:detect-disconnected --minutes=5')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('hechos:notificar-pendientes')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('hechos:corte-pendientes')
            ->weeklyOn(1, '18:05')
            ->withoutOverlapping();

        $schedule->command('hechos:reporte-pendientes --json')
            ->weeklyOn(1, '18:06')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
