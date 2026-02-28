<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Services\WhatsApp\C5IReport;
use App\Services\WhatsApp\NearestUnit;

class WabotIncomingController extends Controller
{
    public function incoming(Request $request)
    {
        $secret = (string) $request->header('X-WABOT-SECRET', '');
        $expected = (string) config('services.wabot.secret', '');

        if ($expected !== '' && $secret !== $expected) {
            return response()->json(['ok' => false], 403);
        }

        $chatId = (string) ($request->input('chat_id') ?? '');
        $text = (string) ($request->input('body') ?? '');
        $messageId = $request->input('message_id');
        $receivedAt = (string) ($request->input('received_at') ?? '');

        $text = trim($text);
        if ($text === '') {
            return response()->json(['ok' => false, 'error' => 'empty'], 422);
        }

        $coords = C5IReport::parseCoordsFromC5IText($text);

        $gmaps = null;
        $recoText = "RECOMENDACIÓN: NO DISPONIBLE (SIN COORDENADAS).";

        if ($coords) {
            $gmaps = C5IReport::googleMapsLinkFromCoords($coords['lat'], $coords['lng']);
            $r = NearestUnit::recommendForCoords((float)$coords['lat'], (float)$coords['lng'], 3);
            $recoText = NearestUnit::recommendationText($r);
        }

        $reply = implode("\n", array_filter([
            "📡 REPORTE C5I",
            $text,
            "",
            $gmaps,
            $recoText,
            "",
            "Te encargo el folio C5I por favor.",
        ], fn ($x) => $x !== null && trim((string)$x) !== ''));

        Log::info('WABOT INCOMING', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'received_at' => $receivedAt,
            'has_coords' => (bool) $coords,
        ]);

        return response()->json([
            'ok' => true,
            'reply' => $reply,
        ], 200);
    }
}
