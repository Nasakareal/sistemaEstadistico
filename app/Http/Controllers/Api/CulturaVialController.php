<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CulturaVialIntento;
use App\Models\CulturaVialParticipante;
use App\Models\CulturaVialSala;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CulturaVialController extends Controller
{
    private const JUEGO_CIUDAD_SEGURA = 'ciudad_segura';

    public function index(Request $request)
    {
        $user = $request->user();

        $query = CulturaVialSala::query()
            ->withCount('participantes')
            ->latest();

        if (!$this->canSeeAllRooms($user)) {
            $query->where('instructor_id', $user->id);
        }

        return response()->json([
            'data' => $query->limit(30)->get()->map(fn ($sala) => $this->roomSummary($sala))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'juego_slug' => ['nullable', 'string', 'max:80'],
        ]);

        $sala = CulturaVialSala::create([
            'codigo' => $this->newRoomCode(),
            'nombre' => trim($validated['nombre'] ?? '') ?: 'Clase de Cultura Vial',
            'juego_slug' => $validated['juego_slug'] ?? self::JUEGO_CIUDAD_SEGURA,
            'estado' => 'abierta',
            'instructor_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Sala creada correctamente.',
            'data' => $this->roomDetails($sala->fresh()),
        ], 201);
    }

    public function show(Request $request, CulturaVialSala $sala)
    {
        $this->authorizeRoom($request, $sala);

        return response()->json([
            'data' => $this->roomDetails($sala),
        ]);
    }

    public function close(Request $request, CulturaVialSala $sala)
    {
        $this->authorizeRoom($request, $sala);

        $sala->forceFill([
            'estado' => 'cerrada',
            'cerrada_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Sala cerrada.',
            'data' => $this->roomDetails($sala->fresh()),
        ]);
    }

    public function qr(Request $request, CulturaVialSala $sala)
    {
        $this->authorizeRoom($request, $sala);

        $qrCode = QrCode::create($this->qrPayload($sala))
            ->setSize(520)
            ->setMargin(18)
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setForegroundColor(new Color(15, 23, 42))
            ->setBackgroundColor(new Color(255, 255, 255));

        $png = (new PngWriter())->write($qrCode)->getString();

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function publicRoom(string $codigo)
    {
        $sala = $this->findRoomByCode($codigo);

        return response()->json([
            'data' => [
                'codigo' => $sala->codigo,
                'nombre' => $sala->nombre,
                'juego_slug' => $sala->juego_slug,
                'estado' => $sala->estado,
                'abierta' => $sala->abierta,
            ],
        ]);
    }

    public function join(Request $request, string $codigo)
    {
        $sala = $this->findRoomByCode($codigo);
        if (!$sala->abierta) {
            return response()->json(['message' => 'La sala ya fue cerrada por el instructor.'], 409);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
        ]);

        $participante = CulturaVialParticipante::create([
            'sala_id' => $sala->id,
            'nombre' => $this->cleanChildName($validated['nombre']),
            'join_token' => Str::random(48),
        ]);

        return response()->json([
            'message' => 'Listo, ya estás en la sala.',
            'data' => [
                'participante' => $this->participantData($participante),
                'sala' => [
                    'codigo' => $sala->codigo,
                    'nombre' => $sala->nombre,
                    'juego_slug' => $sala->juego_slug,
                ],
            ],
        ], 201);
    }

    public function storeAttempt(Request $request, CulturaVialParticipante $participante)
    {
        $validated = $request->validate([
            'join_token' => ['required', 'string', 'max:100'],
            'juego_slug' => ['nullable', 'string', 'max:80'],
            'puntaje' => ['required', 'integer', 'min:0', 'max:100000'],
            'aciertos' => ['required', 'integer', 'min:0', 'max:255'],
            'errores' => ['required', 'integer', 'min:0', 'max:255'],
            'duracion_segundos' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'decisiones' => ['nullable', 'array', 'max:50'],
        ]);

        if (!hash_equals($participante->join_token, $validated['join_token'])) {
            return response()->json(['message' => 'Tu sesión de juego no es válida. Vuelve a entrar a la sala.'], 403);
        }

        $participante->loadMissing('sala');
        if (!$participante->sala || !$participante->sala->abierta) {
            return response()->json(['message' => 'La sala ya fue cerrada por el instructor.'], 409);
        }

        $intento = DB::transaction(function () use ($participante, $validated) {
            $intento = CulturaVialIntento::create([
                'sala_id' => $participante->sala_id,
                'participante_id' => $participante->id,
                'juego_slug' => $validated['juego_slug'] ?? self::JUEGO_CIUDAD_SEGURA,
                'puntaje' => $validated['puntaje'],
                'aciertos' => $validated['aciertos'],
                'errores' => $validated['errores'],
                'duracion_segundos' => $validated['duracion_segundos'] ?? 0,
                'decisiones_json' => $validated['decisiones'] ?? [],
                'terminado_at' => now(),
            ]);

            $participante->forceFill([
                'mejor_puntaje' => max((int) $participante->mejor_puntaje, (int) $validated['puntaje']),
                'intentos' => (int) $participante->intentos + 1,
                'ultimo_intento_at' => now(),
            ])->save();

            return $intento;
        });

        return response()->json([
            'message' => 'Puntaje guardado.',
            'data' => [
                'intento' => $this->attemptData($intento),
                'participante' => $this->participantData($participante->fresh()),
            ],
        ], 201);
    }

    private function roomDetails(CulturaVialSala $sala): array
    {
        $sala->load([
            'participantes' => fn ($query) => $query
                ->orderByDesc('mejor_puntaje')
                ->orderBy('nombre'),
            'participantes.intentos' => fn ($query) => $query
                ->latest()
                ->limit(3),
            'instructor:id,name',
        ]);

        return array_merge($this->roomSummary($sala), [
            'instructor' => $sala->instructor ? [
                'id' => $sala->instructor->id,
                'name' => $sala->instructor->name,
            ] : null,
            'participantes' => $sala->participantes
                ->map(fn ($participante) => $this->participantData($participante, true))
                ->values(),
        ]);
    }

    private function roomSummary(CulturaVialSala $sala): array
    {
        return [
            'id' => $sala->id,
            'codigo' => $sala->codigo,
            'nombre' => $sala->nombre,
            'juego_slug' => $sala->juego_slug,
            'estado' => $sala->estado,
            'abierta' => $sala->abierta,
            'participantes_count' => (int) ($sala->participantes_count ?? $sala->participantes()->count()),
            'join_payload' => $this->qrPayload($sala),
            'qr_url' => url("/api/cultura-vial/salas/{$sala->id}/qr"),
            'created_at' => optional($sala->created_at)->toISOString(),
            'cerrada_at' => optional($sala->cerrada_at)->toISOString(),
        ];
    }

    private function participantData(CulturaVialParticipante $participante, bool $withAttempts = false): array
    {
        $data = [
            'id' => $participante->id,
            'nombre' => $participante->nombre,
            'join_token' => $participante->join_token,
            'mejor_puntaje' => (int) $participante->mejor_puntaje,
            'intentos' => (int) $participante->intentos,
            'ultimo_intento_at' => optional($participante->ultimo_intento_at)->toISOString(),
            'created_at' => optional($participante->created_at)->toISOString(),
        ];

        if ($withAttempts) {
            $data['intentos_recientes'] = $participante->intentos
                ->sortByDesc('created_at')
                ->take(3)
                ->map(fn ($intento) => $this->attemptData($intento))
                ->values()
                ->all();
        }

        return $data;
    }

    private function attemptData(CulturaVialIntento $intento): array
    {
        return [
            'id' => $intento->id,
            'puntaje' => (int) $intento->puntaje,
            'aciertos' => (int) $intento->aciertos,
            'errores' => (int) $intento->errores,
            'duracion_segundos' => (int) $intento->duracion_segundos,
            'terminado_at' => optional($intento->terminado_at)->toISOString(),
        ];
    }

    private function authorizeRoom(Request $request, CulturaVialSala $sala): void
    {
        $user = $request->user();
        if ($this->canSeeAllRooms($user)) {
            return;
        }

        abort_if((int) $sala->instructor_id !== (int) $user->id, 403, 'No tienes acceso a esta sala.');
    }

    private function canSeeAllRooms($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->isSuperadmin() || (int) ($user->unidad_id ?? 0) === 3;
    }

    private function findRoomByCode(string $codigo): CulturaVialSala
    {
        $clean = $this->normalizeCode($codigo);

        return CulturaVialSala::where('codigo', $clean)->firstOrFail();
    }

    private function newRoomCode(): string
    {
        do {
            $code = $this->randomCode(6);
        } while (CulturaVialSala::where('codigo', $code)->exists());

        return $code;
    }

    private function randomCode(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    private function normalizeCode(string $codigo): string
    {
        $raw = Str::upper(trim($codigo));
        $raw = str_replace('SV-CULTURA:', '', $raw);

        return preg_replace('/[^A-Z0-9]/', '', $raw) ?: $raw;
    }

    private function cleanChildName(string $name): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($name));

        return mb_substr($clean ?: 'Participante', 0, 80, 'UTF-8');
    }

    private function qrPayload(CulturaVialSala $sala): string
    {
        return 'SV-CULTURA:' . $sala->codigo;
    }
}
