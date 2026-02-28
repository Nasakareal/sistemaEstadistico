<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Services\WhatsApp\C5IReport;
use App\Services\WhatsApp\NearestUnit;
use App\Services\WhatsApp\WhatsAppBot;

class C5IInboundController extends Controller
{
    public function handle(Request $request)
    {
        $token = $request->header('X-C5I-TOKEN');
        if (!$token || $token !== config('services.c5i.token')) {
            return response()->json(['ok' => false], 403);
        }

        $text = (string) ($request->input('text') ?? '');
        if (trim($text) === '') {
            return response()->json(['ok' => false, 'error' => 'empty'], 422);
        }

        $coords = C5IReport::parseCoordsFromC5IText($text);

        $gmaps = null;
        $recoText = "RECOMENDACIÓN: NO DISPONIBLE (SIN COORDENADAS).";

        if ($coords) {
            $gmaps = C5IReport::googleMapsLinkFromCoords($coords['lat'], $coords['lng']);
            $r = NearestUnit::recommendForCoords($coords['lat'], $coords['lng'], 3);
            $recoText = NearestUnit::recommendationText($r);
        }

        $message = implode("\n", array_filter([
            "📡 REPORTE C5I",
            $text,
            "",
            $gmaps,
            $recoText,
        ], fn($x) => $x !== null));

        $chatId = (string) env('WHATSAPP_DEFAULT_CHAT_ID');

        $resp = WhatsAppBot::sendToChat($chatId, $message, []);

        Log::info('C5I->WA', [
            'coords' => $coords,
            'chatId' => $chatId,
            'resp' => $resp,
        ]);

        if (!($resp['ok'] ?? false)) {
            return response()->json(['ok' => false, 'resp' => $resp], 500);
        }

        return response()->json(['ok' => true], 200);
    }
}
