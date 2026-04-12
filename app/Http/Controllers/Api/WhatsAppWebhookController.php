<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $verifyToken = (string) config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token !== '' && hash_equals($verifyToken, (string) $token)) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WA Cloud webhook', [
            'keys' => array_keys($payload),
            'has_entry' => isset($payload['entry']),
            'payload' => $payload,
        ]);

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                $messages = $value['messages'] ?? [];
                $contacts = $value['contacts'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach ($messages as $message) {
                    Log::info('WA mensaje recibido', [
                        'from' => $message['from'] ?? null,
                        'id' => $message['id'] ?? null,
                        'timestamp' => $message['timestamp'] ?? null,
                        'type' => $message['type'] ?? null,
                        'text' => $message['text']['body'] ?? null,
                        'contact' => $contacts[0] ?? null,
                        'metadata' => $metadata,
                    ]);
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }
}
