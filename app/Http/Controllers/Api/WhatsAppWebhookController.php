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

        Log::info('WA Cloud webhook recibido', [
            'object' => $payload['object'] ?? null,
            'entries_count' => isset($payload['entry']) && is_array($payload['entry']) ? count($payload['entry']) : 0,
        ]);

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach (($value['messages'] ?? []) as $message) {
                    Log::info('WA mensaje recibido', [
                        'from' => $message['from'] ?? null,
                        'id' => $message['id'] ?? null,
                        'timestamp' => $message['timestamp'] ?? null,
                        'type' => $message['type'] ?? null,
                        'text' => $message['text']['body'] ?? null,
                        'display_phone_number' => $metadata['display_phone_number'] ?? null,
                        'phone_number_id' => $metadata['phone_number_id'] ?? null,
                    ]);
                }

                foreach (($value['statuses'] ?? []) as $status) {
                    Log::info('WA estado mensaje', [
                        'id' => $status['id'] ?? null,
                        'status' => $status['status'] ?? null,
                        'timestamp' => $status['timestamp'] ?? null,
                        'recipient_id' => $status['recipient_id'] ?? null,
                        'conversation_id' => $status['conversation']['id'] ?? null,
                        'conversation_origin' => $status['conversation']['origin']['type'] ?? null,
                        'pricing_billable' => $status['pricing']['billable'] ?? null,
                        'pricing_category' => $status['pricing']['category'] ?? null,
                        'pricing_model' => $status['pricing']['pricing_model'] ?? null,
                        'error_code' => $status['errors'][0]['code'] ?? null,
                        'error_title' => $status['errors'][0]['title'] ?? null,
                        'error_message' => $status['errors'][0]['message'] ?? null,
                        'display_phone_number' => $metadata['display_phone_number'] ?? null,
                        'phone_number_id' => $metadata['phone_number_id'] ?? null,
                    ]);
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }
}
