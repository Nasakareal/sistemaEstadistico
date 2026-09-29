<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstanciaExamenSolicitud;
use App\Models\ConstanciaManejo;
use App\Models\LicenciaConducir;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LicenciaEmisionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);

        $query = LicenciaConducir::query()
            ->with(['constancia.examen', 'examenSolicitud', 'usuario'])
            ->orderByDesc('id');

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->query('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('numero', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhereHas('constancia', function ($constancia) use ($buscar) {
                        $constancia->where('folio', 'like', "%{$buscar}%");
                    });
            });
        }

        $page = $query->paginate(max(1, min((int) $request->query('per_page', 25), 100)));

        return response()->json([
            'data' => collect($page->items())->map(function ($licencia) {
                return $this->payload($licencia);
            })->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(Request $request, LicenciaConducir $licencia)
    {
        $this->authorizeSuperadmin($request);

        return response()->json(['data' => $this->payload($licencia)]);
    }

    public function buscarQr(Request $request, string $token)
    {
        $this->authorizeSuperadmin($request);
        $licencia = LicenciaConducir::where('qr_token', trim($token))->firstOrFail();

        return response()->json(['data' => $this->payload($licencia)]);
    }

    public function historial(Request $request, string $curp)
    {
        $this->authorizeSuperadmin($request);
        $items = LicenciaConducir::query()
            ->with(['constancia.examen', 'examenSolicitud', 'usuario'])
            ->where('curp', strtoupper(trim($curp)))
            ->orderByDesc('fecha_expedicion')
            ->orderByDesc('id')
            ->get()
            ->map(function ($licencia) {
                return $this->payload($licencia);
            })
            ->values();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $user = $this->authorizeSuperadmin($request);
        $validated = $request->validate([
            'constancia_id' => ['required', 'integer', 'exists:constancias_manejo,id', 'unique:licencias_conducir,constancia_id'],
            'numero' => ['nullable', 'string', 'max:40', 'unique:licencias_conducir,numero'],
            'curp' => ['required', 'string', 'size:18'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'nombres' => ['required', 'string', 'max:150'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'fecha_expedicion' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after:fecha_expedicion'],
            'fecha_antiguedad' => ['nullable', 'date', 'before_or_equal:fecha_expedicion'],
            'tipo_licencia' => ['required', 'string', 'max:40'],
            'genero' => ['required', Rule::in(['H', 'M', 'X'])],
            'tipo_sangre' => ['required', 'string', 'max:10'],
            'donador_organos' => ['required', 'boolean'],
            'restricciones' => ['nullable', 'string', 'max:255'],
            'oficina_emisora' => ['required', 'string', 'max:150'],
            'vehiculos_autorizados' => ['required', 'string', 'max:1000'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $constancia = ConstanciaManejo::with('examen')->findOrFail((int) $validated['constancia_id']);
        abort_unless($constancia->estatus === 'ACTIVA', 422, 'La constancia debe estar activa antes de emitir la licencia.');
        abort_unless($constancia->tieneExamenAprobado(), 422, 'La constancia debe tener un examen de manejo aprobado.');

        $solicitud = ConstanciaExamenSolicitud::query()
            ->where('constancia_id', $constancia->id)
            ->where('estatus', 'APROBADO')
            ->latest('id')
            ->first();
        abort_unless($solicitud, 422, 'No se encontro el examen aprobado vinculado a la constancia.');

        $curp = strtoupper(trim((string) $validated['curp']));
        if ($constancia->curp && strtoupper(trim($constancia->curp)) !== $curp) {
            abort(422, 'La CURP no coincide con la registrada en la constancia.');
        }

        $fotoPath = $request->file('foto')->store('licencias/fotos', 'public');

        try {
            $licencia = DB::transaction(function () use ($validated, $curp, $constancia, $solicitud, $user, $fotoPath) {
                $licencia = LicenciaConducir::create([
                    'constancia_id' => $constancia->id,
                    'examen_solicitud_id' => $solicitud->id,
                    'user_id' => $user->id,
                    'numero' => trim((string) ($validated['numero'] ?? '')) ?: 'TMP-' . Str::uuid(),
                    'qr_token' => (string) Str::uuid(),
                    'curp' => $curp,
                    'apellido_paterno' => trim($validated['apellido_paterno']),
                    'apellido_materno' => trim((string) ($validated['apellido_materno'] ?? '')) ?: null,
                    'nombres' => trim($validated['nombres']),
                    'fecha_nacimiento' => $validated['fecha_nacimiento'],
                    'fecha_expedicion' => $validated['fecha_expedicion'],
                    'fecha_vencimiento' => $validated['fecha_vencimiento'],
                    'fecha_antiguedad' => $validated['fecha_antiguedad'] ?? null,
                    'tipo_licencia' => trim($validated['tipo_licencia']),
                    'genero' => $validated['genero'],
                    'tipo_sangre' => strtoupper(trim($validated['tipo_sangre'])),
                    'donador_organos' => (bool) $validated['donador_organos'],
                    'restricciones' => trim((string) ($validated['restricciones'] ?? '')) ?: 'NINGUNA',
                    'oficina_emisora' => trim($validated['oficina_emisora']),
                    'vehiculos_autorizados' => trim($validated['vehiculos_autorizados']),
                    'foto_path' => $fotoPath,
                    'estatus' => 'VIGENTE',
                ]);

                if (Str::startsWith($licencia->numero, 'TMP-')) {
                    $licencia->numero = '11' . now()->format('Y') . str_pad((string) $licencia->id, 7, '0', STR_PAD_LEFT);
                    $licencia->save();
                }

                return $licencia;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($fotoPath);
            throw $e;
        }

        return response()->json([
            'message' => 'Licencia emitida y vinculada al expediente.',
            'data' => $this->payload($licencia),
        ], 201);
    }

    private function authorizeSuperadmin(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperadmin(), 403, 'Solo Superadmin puede emitir y consultar licencias.');

        return $user;
    }

    private function payload(LicenciaConducir $licencia): array
    {
        $licencia->loadMissing(['constancia.examen', 'examenSolicitud', 'usuario']);
        $constancia = $licencia->constancia;
        $solicitud = $licencia->examenSolicitud;
        $qrValue = 'SV-LICENCIA:' . $licencia->qr_token;

        return [
            'id' => $licencia->id,
            'numero' => $licencia->numero,
            'curp' => $licencia->curp,
            'apellido_paterno' => $licencia->apellido_paterno,
            'apellido_materno' => $licencia->apellido_materno,
            'nombres' => $licencia->nombres,
            'nombre_completo' => trim($licencia->nombres . ' ' . $licencia->apellido_paterno . ' ' . $licencia->apellido_materno),
            'fecha_nacimiento' => optional($licencia->fecha_nacimiento)->format('Y-m-d'),
            'fecha_expedicion' => optional($licencia->fecha_expedicion)->format('Y-m-d'),
            'fecha_vencimiento' => optional($licencia->fecha_vencimiento)->format('Y-m-d'),
            'fecha_antiguedad' => optional($licencia->fecha_antiguedad)->format('Y-m-d'),
            'tipo_licencia' => $licencia->tipo_licencia,
            'genero' => $licencia->genero,
            'tipo_sangre' => $licencia->tipo_sangre,
            'donador_organos' => $licencia->donador_organos,
            'restricciones' => $licencia->restricciones,
            'oficina_emisora' => $licencia->oficina_emisora,
            'vehiculos_autorizados' => $licencia->vehiculos_autorizados,
            'estatus' => $licencia->estatus,
            'foto_url' => Storage::disk('public')->url($licencia->foto_path),
            'qr_base64' => base64_encode($this->qrPng($qrValue)),
            'emitida_por' => $licencia->usuario->name ?? null,
            'created_at' => optional($licencia->created_at)->toISOString(),
            'constancia' => $constancia ? [
                'id' => $constancia->id,
                'folio' => $constancia->folio,
                'estatus' => $constancia->estatus,
                'fecha_activacion' => optional($constancia->fecha_activacion)->toISOString(),
            ] : null,
            'examen' => $solicitud ? [
                'id' => $solicitud->id,
                'folio' => $solicitud->folio_examen,
                'estatus' => $solicitud->estatus,
                'calificacion' => $solicitud->calificacion,
                'fecha_examen' => optional($solicitud->fecha_examen)->toISOString(),
            ] : null,
        ];
    }

    private function qrPng(string $value): string
    {
        $qrCode = QrCode::create($value)
            ->setSize(420)
            ->setMargin(14)
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setForegroundColor(new Color(15, 23, 42))
            ->setBackgroundColor(new Color(255, 255, 255));

        return (new PngWriter())->write($qrCode)->getString();
    }
}
