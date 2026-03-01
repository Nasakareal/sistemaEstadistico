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
        $body = trim((string) ($request->input('body') ?? ''));

        if ($chatId === '' || $body === '') {
            return response()->json(['ok' => false, 'error' => 'missing'], 422);
        }

        $dedupeKey = 'wabot_incoming:' . sha1($chatId . '|' . $body);
        if (cache()->has($dedupeKey)) {
            return response()->json([
                'ok' => true,
                'sent' => false,
                'reply' => '',
                'whatsapp_message_id' => '',
            ], 200);
        }
        cache()->put($dedupeKey, 1, now()->addSeconds(90));

        $coords = C5IReport::parseCoordsFromC5IText($body);

        $gmaps = null;
        $recoText = "RECOMENDACIÓN: NO DISPONIBLE (SIN COORDENADAS).";

        if ($coords) {
            $gmaps = C5IReport::googleMapsLinkFromCoords((float) $coords['lat'], (float) $coords['lng']);
            $r = NearestUnit::recommendForCoords((float) $coords['lat'], (float) $coords['lng'], 3);
            $recoText = NearestUnit::recommendationText($r);
        }

        $parts = [];

        if ($gmaps) {
            $parts[] = $gmaps;
        }

        $parts[] = $recoText;
        $parts[] = "Te encargo el folio C5I por favor.";

        $reply = implode(' ', array_values(array_filter($parts, fn($x) => $x !== null && trim($x) !== '')));

        $resp = \App\Services\WhatsApp\WhatsAppBot::sendToChat($chatId, $reply, []);

        if (!($resp['ok'] ?? false) || empty($resp['id'])) {
            return response()->json([
                'ok' => false,
                'error' => 'send_failed',
                'data' => $resp,
                'reply' => $reply,
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'sent' => true,
            'reply' => $reply,
            'whatsapp_message_id' => (string) ($resp['id'] ?? ''),
        ], 200);
    }
}
