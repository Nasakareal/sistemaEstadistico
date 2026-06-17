<?php

namespace App\Console\Commands\Licencias;

use App\Services\LicenciaPuntosService;
use Illuminate\Console\Command;

class RecuperarPuntosLicencias extends Command
{
    protected $signature = 'licencias-puntos:recuperar';

    protected $description = 'Recupera automaticamente puntos de licencias con 18 meses sin infracciones.';

    public function handle(LicenciaPuntosService $service): int
    {
        $recuperadas = $service->recuperarCuentasElegibles();

        $this->info("Licencias recuperadas: {$recuperadas}");

        return self::SUCCESS;
    }
}
