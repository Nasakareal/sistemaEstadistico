<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $verifyToken = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        // Meta recomienda responder 200 rápido
        $payload = $request->all();

        // Log mínimo para depurar
        Log::info('WhatsApp webhook event', [
            'has_entry' => isset($payload['entry']),
            'keys' => array_keys($payload),
        ]);

        // Aquí después :
        // - Guardar mensajes entrantes
        // - Detectar comandos ("HECHO 123", "STATUS", etc.)
        // - Responder automáticamente

        return response()->json(['ok' => true], 200);
    }
}
