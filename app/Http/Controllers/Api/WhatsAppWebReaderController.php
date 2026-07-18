<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppWebGroup;
use App\Models\WhatsAppWebMessage;
use App\Services\C5iSiniestrosRecommendationService;
use App\Services\C5iResponseTimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WhatsAppWebReaderController extends Controller
{
    public function syncGroups(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['ok' => false], $this->authorizationStatus());
        }

        $data = $request->validate([
            'groups' => ['required', 'array', 'max:500'],
            'groups.*.id' => ['required', 'string', 'max:191'],
            'groups.*.name' => ['nullable', 'string', 'max:255'],
            'groups.*.participant_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $stored = [];

        foreach ($data['groups'] as $groupData) {
            if (!$this->identifierAllowed(
                (string) $groupData['id'],
                (string) config('services.whatsapp.web_reader.allowed_group_ids', '')
            )) {
                continue;
            }

            $group = WhatsAppWebGroup::query()->updateOrCreate(
                ['whatsapp_id' => $groupData['id']],
                [
                    'name' => $groupData['name'] ?? null,
                    'participant_count' => (int) ($groupData['participant_count'] ?? 0),
                    'last_seen_at' => now(),
                ]
            );

            $stored[] = [
                'id' => $group->whatsapp_id,
                'name' => $group->name,
            ];
        }

        return response()->json([
            'ok' => true,
            'stored' => count($stored),
            'groups' => $stored,
        ]);
    }

    public function storeMessage(Request $request, C5iResponseTimeService $responseTime)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['ok' => false], $this->authorizationStatus());
        }

        $data = $request->validate([
            'group.id' => ['required', 'string', 'max:191'],
            'group.name' => ['nullable', 'string', 'max:255'],
            'message.id' => ['nullable', 'string', 'max:191'],
            'message.quoted_message_id' => ['nullable', 'string', 'max:191'],
            'message.author_id' => ['nullable', 'string', 'max:191'],
            'message.body' => ['nullable', 'string', 'max:65535'],
            'message.type' => ['nullable', 'string', 'max:50'],
            'message.has_media' => ['nullable', 'boolean'],
            'message.timestamp' => ['required', 'integer', 'min:1'],
        ]);

        if (!$this->identifierAllowed(
            (string) $data['group']['id'],
            (string) config('services.whatsapp.web_reader.allowed_group_ids', '')
        )) {
            return response()->json([
                'ok' => true,
                'stored' => false,
                'reason' => 'group_not_allowed',
            ], 202);
        }

        $authorAllowed = $this->identifierAllowed(
            (string) ($data['message']['author_id'] ?? ''),
            (string) config('services.whatsapp.web_reader.allowed_author_ids', '')
        );
        $operationalAuthorAllowed = (bool) config(
            'services.whatsapp.web_reader.allow_operational_authors',
            false
        ) && $responseTime->isOperationalMessageCandidate($data['message']['body'] ?? null);

        if (!$authorAllowed && !$operationalAuthorAllowed) {
            return response()->json([
                'ok' => true,
                'stored' => false,
                'reason' => 'author_not_allowed',
            ], 202);
        }

        $group = WhatsAppWebGroup::query()->updateOrCreate(
            ['whatsapp_id' => $data['group']['id']],
            [
                'name' => $data['group']['name'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        $whatsappMessageId = trim((string) ($data['message']['id'] ?? ''));

        if ($whatsappMessageId === '') {
            $signature = [
                'group_id' => $data['group']['id'],
                'author_id' => $data['message']['author_id'] ?? null,
                'body' => $data['message']['body'] ?? null,
                'type' => $data['message']['type'] ?? 'unknown',
                'has_media' => (bool) ($data['message']['has_media'] ?? false),
                'timestamp' => (int) $data['message']['timestamp'],
            ];

            if (!empty($data['message']['quoted_message_id'])) {
                $signature['quoted_message_id'] = $data['message']['quoted_message_id'];
            }

            $whatsappMessageId = 'fallback_' . hash('sha256', json_encode(
                $signature,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
        }

        $message = WhatsAppWebMessage::query()->updateOrCreate(
            ['whatsapp_message_id' => $whatsappMessageId],
            [
                'whatsapp_web_group_id' => $group->id,
                'quoted_whatsapp_message_id' => $data['message']['quoted_message_id'] ?? null,
                'author_whatsapp_id' => $data['message']['author_id'] ?? null,
                'body' => $data['message']['body'] ?? null,
                'message_type' => $data['message']['type'] ?? 'unknown',
                'has_media' => (bool) ($data['message']['has_media'] ?? false),
                'sent_at' => Carbon::createFromTimestampUTC((int) $data['message']['timestamp']),
            ]
        );

        if ($message->wasRecentlyCreated
            && (bool) config('services.whatsapp.c5i_recommendation.enabled', false)) {
            app(C5iSiniestrosRecommendationService::class)->process($message);
        }

        $responseTimeResult = $message->wasRecentlyCreated
            ? $responseTime->processMessage($message)
            : ['status' => 'duplicate'];

        return response()->json([
            'ok' => true,
            'message_id' => $message->id,
            'recommendation_status' => $message->recommendation_status,
            'response_time_status' => $responseTimeResult['status'] ?? null,
        ]);
    }

    protected function isAuthorized(Request $request): bool
    {
        $expected = trim((string) config('services.whatsapp.web_reader.secret', ''));
        $provided = (string) $request->header('X-WhatsApp-Reader-Secret', '');

        return $expected !== '' && hash_equals($expected, $provided);
    }

    protected function authorizationStatus(): int
    {
        return trim((string) config('services.whatsapp.web_reader.secret', '')) === '' ? 503 : 403;
    }

    protected function identifierAllowed(string $identifier, string $configured): bool
    {
        $identifier = mb_strtolower(trim($identifier), 'UTF-8');
        $identifierDigits = preg_replace('/\D+/', '', $identifier) ?: '';
        $allowed = preg_split('/[\s,;|]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($allowed ?: [] as $value) {
            $value = mb_strtolower(trim((string) $value), 'UTF-8');

            if ($identifier !== '' && hash_equals($value, $identifier)) {
                return true;
            }

            $allowedDigits = preg_replace('/\D+/', '', $value) ?: '';

            if ($identifierDigits !== ''
                && $allowedDigits !== ''
                && hash_equals($allowedDigits, $identifierDigits)) {
                return true;
            }
        }

        return false;
    }
}
