<?php

namespace App\Services\WhatsApp;

class WhatsAppInboundService
{
    public function extractMessages(array $payload): array
    {
        $messages = [];

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach (($value['messages'] ?? []) as $message) {
                    $message['_metadata'] = $metadata;
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    public function extractStatuses(array $payload): array
    {
        $statuses = [];

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach (($value['statuses'] ?? []) as $status) {
                    $statuses[] = [
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
                    ];
                }
            }
        }

        return $statuses;
    }

    public function extractUserInput(array $message): array
    {
        $type = (string) ($message['type'] ?? '');

        if ($type === 'text') {
            return [
                'type' => 'text',
                'value' => trim((string) ($message['text']['body'] ?? '')),
            ];
        }

        if ($type === 'interactive') {
            if (!empty($message['interactive']['button_reply']['id'])) {
                return [
                    'type' => 'button',
                    'value' => trim((string) $message['interactive']['button_reply']['id']),
                    'title' => trim((string) ($message['interactive']['button_reply']['title'] ?? '')),
                ];
            }

            if (!empty($message['interactive']['list_reply']['id'])) {
                return [
                    'type' => 'list',
                    'value' => trim((string) $message['interactive']['list_reply']['id']),
                    'title' => trim((string) ($message['interactive']['list_reply']['title'] ?? '')),
                ];
            }
        }

        if ($type === 'button' && !empty($message['button']['text'])) {
            return [
                'type' => 'button',
                'value' => trim((string) $message['button']['text']),
            ];
        }

        return [
            'type' => $type !== '' ? $type : 'unknown',
            'value' => '',
        ];
    }
}
