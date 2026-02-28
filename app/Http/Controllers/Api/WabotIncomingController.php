<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\WhatsApp\C5IReport;
use App\Services\WhatsApp\NearestUnit;

class WabotIncomingController extends Controller
{
    public function handle(Request $request)
    {
        $secret = (string) $request->header('X-WABOT-SECRET', '');
        $expected = (string) config('services.wabot.secret');

        if ($expected !== '' && !hash_equals($expected, $secret)) {
            return response()->json(['ok' => false], 403);
        }

        $chatId = (string) ($request->input('chat_id') ?? '');
        $body = (string) ($request->input('body') ?? '');

        $body = trim($body);

        if ($chatId === '' || $body === '') {
            return response()->json(['ok' => false, 'error' => 'missing'], 422);
        }

        $coords = C5IReport::parseCoordsFromC5IText($body);

        $gmaps = null;
        $recoText = "RECOMENDACIÓN: NO DISPONIBLE (SIN COORDENADAS).";

        if ($coords) {
            $gmaps = C5IReport::googleMapsLinkFromCoords($coords['lat'], $coords['lng']);
            $r = NearestUnit::recommendForCoords((float)$coords['lat'], (float)$coords['lng'], 3);
            $recoText = NearestUnit::recommendationText($r);
        }

        $replyParts = [];
        $replyParts[] = "📡 REPORTE C5I";
        $replyParts[] = $body;
        if ($gmaps) $replyParts[] = $gmaps;
        $replyParts[] = $recoText;
        $replyParts[] = "Te encargo el folio C5I por favor.";

        $reply = implode("\n\n", array_values(array_filter($replyParts, fn($x) => $x !== null && trim($x) !== '')));

        return response()->json([
            'ok' => true,
            'reply' => $reply,
        ], 200);
    }
}
