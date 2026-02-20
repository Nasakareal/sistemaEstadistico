<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hechos;
use App\Models\WazeAlert;
use App\Helpers\StreetNormalizer;

class NormalizeOldStreets extends Command
{
    protected $signature = 'streets:normalize-old';
    protected $description = 'Normaliza calles antiguas en hechos y waze_alerts';

    public function handle()
    {
        $this->info('Normalizando HECHOS...');

        $hechos = Hechos::whereNull('calle_norm')->get();

        foreach ($hechos as $h) {
            $h->calle_norm = StreetNormalizer::normalize($h->calle);
            $h->save();
        }

        $this->info('Normalizando WAZE ALERTS...');

        $alerts = WazeAlert::whereNull('street_norm')->get();

        foreach ($alerts as $a) {
            $a->street_norm = StreetNormalizer::normalize($a->street);
            $a->save();
        }

        $this->info('✔ Normalización completa.');
    }
}
