<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Services\IpGeolocationService;
use Illuminate\Console\Command;

class EnrichHoneypotLocations extends Command
{
    protected $signature = 'security:geolocate-honeypot {--limit= : Número máximo de eventos}';

    protected $description = 'Añade ubicación aproximada por IP a eventos del honeypot pendientes';

    public function handle(IpGeolocationService $geolocation): int
    {
        if (!config('honeypot_api.geolocation.enabled')) {
            $this->line('La geolocalización del honeypot está desactivada.');
            return 0;
        }

        $limit = max(1, min(500, (int) ($this->option('limit') ?: config('honeypot_api.geolocation.batch_size', 50))));
        $events = SecurityEvent::query()
            ->where('event_code', 'like', 'honeypot\_%')
            ->whereNotNull('ip_address')
            ->orderByDesc('last_seen_at')
            ->limit($limit * 4)
            ->get()
            ->filter(fn (SecurityEvent $event) => empty($event->metadata['source_geo']))
            ->take($limit);

        $updated = 0;
        foreach ($events as $event) {
            $location = $geolocation->locate((string) $event->ip_address);
            if ($location === null) {
                continue;
            }

            $metadata = (array) $event->metadata;
            $metadata['source_geo'] = $location;
            $event->forceFill(['metadata' => $metadata])->save();
            $updated++;
        }

        $this->info("Se geolocalizaron {$updated} eventos del honeypot.");

        return 0;
    }
}
