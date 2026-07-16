<?php

namespace App\Console\Commands;

use App\Models\WhatsAppWebMessage;
use App\Services\C5iSiniestrosRecommendationService;
use Illuminate\Console\Command;

class SimularC5iSiniestrosRecommendation extends Command
{
    protected $signature = 'whatsapp:c5i-recomendacion-simular
        {message_id? : ID local de whatsapp_web_messages; por defecto usa el último con coordenadas}';

    protected $description = 'Calcula la recomendación C5i/Siniestros sin realizar envíos a Meta';

    public function handle(C5iSiniestrosRecommendationService $service): int
    {
        $messageId = $this->argument('message_id');
        $query = WhatsAppWebMessage::query()->with('group');

        $message = $messageId
            ? $query->find($messageId)
            : $query
                ->where('body', 'like', '%LATITUD:%')
                ->where('body', 'like', '%LONGITUD:%')
                ->latest('id')
                ->first();

        if (!$message) {
            $this->error('No se encontró un mensaje C5i con coordenadas para simular.');
            return self::FAILURE;
        }

        config([
            'services.whatsapp.c5i_recommendation.enabled' => true,
            'services.whatsapp.c5i_recommendation.dry_run' => true,
        ]);

        $result = $service->process($message);
        $message->refresh();

        $this->line(json_encode([
            'message_id' => $message->id,
            'author_whatsapp_id' => $message->author_whatsapp_id,
            'status' => $result['status'] ?? $message->recommendation_status,
            'reason' => $result['reason'] ?? data_get($message->recommendation_meta, 'reason'),
            'incident_lat' => $message->incident_lat,
            'incident_lng' => $message->incident_lng,
            'recommended_patrulla_id' => $message->recommended_patrulla_id,
            'recommendation_distance_km' => $message->recommendation_distance_km,
            'candidate' => data_get($message->recommendation_meta, 'candidate'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ($result['status'] ?? null) === 'dry_run' ? self::SUCCESS : self::FAILURE;
    }
}

