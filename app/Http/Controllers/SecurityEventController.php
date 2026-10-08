<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Services\SecurityEventRecorder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityEventController extends Controller
{
    public function storeInterfaceIntegrity(Request $request, SecurityEventRecorder $recorder)
    {
        $user = $request->user();

        abort_unless(
            $user
            && $user->can('ver hechos')
            && (int) ($user->unidad_id ?? 0) !== 5,
            403
        );

        $data = $request->validate([
            'element' => ['required', 'in:menu_siniestros'],
            'detected_state' => [
                'required',
                'in:element_absent,hidden_attribute,hidden_class,inline_hidden,label_modified,structure_modified,computed_hidden',
            ],
            'restored' => ['required', 'boolean'],
            'page_path' => ['nullable', 'string', 'max:1000'],
        ]);

        $recorder->record(
            'dom_element_removed_or_modified',
            'El navegador detectó una alteración sospechosa del menú Siniestros y ejecutó su restauración.',
            'warning',
            'interface_integrity',
            $request,
            [
                'module' => 'Siniestros / Choques',
                'element' => 'menu_siniestros',
                'original_value' => 'Menú Siniestros autorizado, visible y con su estructura original.',
                'detected_value' => $data['detected_state'],
                'restored' => (bool) $data['restored'],
                'page_path' => $this->normalizePagePath($data['page_path'] ?? null),
                'classification' => 'suspicious',
                'evidence_scope' => 'client_reported_detection',
            ]
        );

        return response()->json(['recorded' => true], 201);
    }

    public function index(Request $request)
    {
        abort_unless($request->user() && $request->user()->hasRole('Superadmin'), 403);

        $filters = $request->validate([
            'severity' => ['nullable', 'in:info,warning,high,critical'],
            'category' => ['nullable', 'string', 'max:50'],
            'event' => ['nullable', 'string', 'max:100'],
            'ip' => ['nullable', 'ip'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $query = SecurityEvent::query();

        if (($filters['event'] ?? null) !== 'authenticated_rate_limit_reached') {
            $query->withoutKnownOperationalNoise();
        }

        $query->with('user:id,name,email');
        $this->applyFilters($query, $filters);

        $events = $query
            ->orderByDesc('last_seen_at')
            ->paginate(50)
            ->appends($request->query());

        $since = Carbon::now()->subDay();
        $summaryBase = SecurityEvent::withoutKnownOperationalNoise()
            ->where('last_seen_at', '>=', $since);

        $summary = [
            'total' => (int) (clone $summaryBase)->sum('occurrences'),
            'high' => (int) (clone $summaryBase)->whereIn('severity', ['high', 'critical'])->sum('occurrences'),
            'failed_logins' => (int) (clone $summaryBase)->where('event_code', 'login_failed')->sum('occurrences'),
            'ips' => (int) (clone $summaryBase)->whereNotNull('ip_address')->distinct()->count('ip_address'),
        ];

        $topIps = SecurityEvent::withoutKnownOperationalNoise()
            ->where('last_seen_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->select([
                'ip_address',
                DB::raw('SUM(occurrences) as total_events'),
                DB::raw("SUM(CASE WHEN severity IN ('high', 'critical') THEN occurrences ELSE 0 END) as high_events"),
                DB::raw('MAX(last_seen_at) as last_seen'),
            ])
            ->groupBy('ip_address')
            ->orderByDesc('high_events')
            ->orderByDesc('total_events')
            ->limit(12)
            ->get();

        $categories = SecurityEvent::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $eventCodes = SecurityEvent::query()
            ->select('event_code')
            ->distinct()
            ->orderBy('event_code')
            ->pluck('event_code');

        $reviewThreshold = (int) config('security_logging.review_ip_threshold', 20);

        return view('admin.settings.security_events.index', compact(
            'events',
            'summary',
            'topIps',
            'categories',
            'eventCodes',
            'filters',
            'reviewThreshold'
        ));
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['event'])) {
            $query->where('event_code', $filters['event']);
        }

        if (!empty($filters['ip'])) {
            $query->where('ip_address', $filters['ip']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('last_seen_at', '>=', Carbon::createFromFormat('Y-m-d', $filters['from'])->startOfDay());
        }

        if (!empty($filters['to'])) {
            $query->where('last_seen_at', '<=', Carbon::createFromFormat('Y-m-d', $filters['to'])->endOfDay());
        }

        if (!empty($filters['search'])) {
            $search = '%' . addcslashes(trim($filters['search']), '%_\\') . '%';
            $query->where(function ($nested) use ($search) {
                $nested->where('description', 'like', $search)
                    ->orWhere('path', 'like', $search)
                    ->orWhere('route_name', 'like', $search)
                    ->orWhere('user_agent', 'like', $search);
            });
        }
    }

    private function normalizePagePath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);

        return is_string($path) ? '/' . ltrim($path, '/') : null;
    }
}
