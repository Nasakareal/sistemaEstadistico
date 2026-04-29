<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaExamen;
use App\Models\ConstanciaFolio;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaModulo;
use Carbon\Carbon;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ConstanciaManejoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeConstanciasUnidad();

        $query = $this->queryConstanciasPermitidas()
            ->with(['modulo', 'examen', 'peritoActivador'])
            ->orderByDesc('id');

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->query('estatus'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->query('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhere('nombre_solicitante', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 25), 50));
        $page = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $page->getCollection()->map(fn ($constancia) => $this->constanciaPayload($constancia))->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function modulos()
    {
        $this->authorizeConstanciasUnidad();

        $modulos = $this->queryModulosPermitidos()->get()->map(function ($modulo) {
            return [
                'id' => $modulo->id,
                'nombre' => $modulo->nombre,
                'tipo' => $modulo->tipo,
                'municipio' => $modulo->municipio,
                'delegacion_id' => $modulo->delegacion_id,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'data' => $modulos,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeConstanciasUnidad();

        $request->validate([
            'modulo_id' => ['required', 'integer', 'exists:constancia_modulos,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $moduloPermitido = $this->queryModulosPermitidos()
            ->where('id', $request->input('modulo_id'))
            ->exists();

        if (!$moduloPermitido) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para generar constancias en este modulo.',
            ], 403);
        }

        $ids = DB::transaction(function () use ($request) {
            return $this->crearConstancias((int) $request->input('modulo_id'), (int) $request->input('cantidad'));
        });

        $constancias = $this->queryConstanciasPermitidas()
            ->with(['modulo', 'examen', 'peritoActivador'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($constancia) => array_search($constancia->id, $ids, true))
            ->values();

        return response()->json([
            'ok' => true,
            'message' => count($ids) . ' constancias generadas.',
            'ids' => $ids,
            'url_imprimir_lote' => $this->signedPrintUrl($ids),
            'data' => $constancias->map(fn ($constancia) => $this->constanciaPayload($constancia))->values(),
        ], 201);
    }

    public function buscarPorQr($token)
    {
        $this->authorizeConstanciasUnidad();

        $constancia = ConstanciaManejo::with(['modulo', 'examen', 'peritoActivador'])
            ->where('qr_token', $token)
            ->first();

        if (!$constancia) {
            return response()->json([
                'ok' => false,
                'message' => 'Constancia no encontrada.'
            ], 404);
        }

        $this->authorizeConstancia($constancia);

        return response()->json([
            'ok' => true,
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function show(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        $constancia->load(['modulo', 'examen', 'peritoActivador']);

        return response()->json([
            'ok' => true,
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function generarAcceso(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no esta disponible.'
            ], 400);
        }

        $request->validate([
            'nombre_solicitante' => 'required|string|max:255',
            'curp' => 'nullable|string|max:18',
            'telefono' => 'nullable|string|max:20',
            'tipo_licencia' => 'required|in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO',
        ]);

        $constancia->update([
            'nombre_solicitante' => mb_strtoupper($request->nombre_solicitante, 'UTF-8'),
            'curp' => $request->curp ? mb_strtoupper($request->curp, 'UTF-8') : null,
            'telefono' => $request->telefono,
            'tipo_licencia' => $request->tipo_licencia,
            'tipo_examen' => 'LINEA',
            'acceso_examen_token' => Str::random(60),
            'acceso_examen_expira' => Carbon::now('America/Mexico_City')->addMinutes(30),
        ]);

        $constancia->load(['modulo', 'examen', 'peritoActivador']);

        return response()->json([
            'ok' => true,
            'message' => 'Acceso generado.',
            'url_examen' => $this->examenUrl($constancia),
            'url_examen_qr' => $this->examenQrUrl($constancia),
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function capturarImpreso(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no esta disponible.'
            ], 400);
        }

        $request->validate([
            'nombre_solicitante' => 'required|string|max:255',
            'curp' => 'nullable|string|max:18',
            'telefono' => 'nullable|string|max:20',
            'tipo_licencia' => 'required|in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO',
            'calificacion' => 'nullable|numeric|min:0|max:100',
            'total_preguntas' => 'required|integer|min:1',
            'aciertos' => 'required|integer|min:0',
            'errores' => 'required|integer|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if (($request->aciertos + $request->errores) != $request->total_preguntas) {
            return response()->json([
                'ok' => false,
                'message' => 'Aciertos y errores no coinciden.'
            ], 400);
        }

        $calificacion = round(($request->aciertos / $request->total_preguntas) * 100, 2);
        $resultado = $calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

        DB::transaction(function () use ($request, $constancia, $resultado, $calificacion) {
            $constancia->update([
                'nombre_solicitante' => mb_strtoupper($request->nombre_solicitante, 'UTF-8'),
                'curp' => $request->curp ? mb_strtoupper($request->curp, 'UTF-8') : null,
                'telefono' => $request->telefono,
                'tipo_licencia' => $request->tipo_licencia,
                'tipo_examen' => 'IMPRESO',
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaExamen::updateOrCreate(
                ['constancia_id' => $constancia->id],
                [
                    'modalidad' => 'IMPRESO',
                    'calificacion' => $calificacion,
                    'total_preguntas' => $request->total_preguntas,
                    'aciertos' => $request->aciertos,
                    'errores' => $request->errores,
                    'resultado' => $resultado,
                    'capturado_por' => auth()->id(),
                    'fecha_examen' => Carbon::now('America/Mexico_City'),
                    'observaciones' => $request->observaciones,
                ]
            );
        });

        $constancia->load(['modulo', 'examen', 'peritoActivador']);

        return response()->json([
            'ok' => true,
            'message' => 'Examen capturado.',
            'resultado' => $resultado,
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function activar(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        $constancia->load('examen');

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no esta disponible.'
            ], 400);
        }

        if (!$constancia->nombre_solicitante || !$constancia->tipo_licencia || !$constancia->tipo_examen) {
            return response()->json([
                'ok' => false,
                'message' => 'Faltan datos del solicitante, tipo de licencia o tipo de examen.'
            ], 400);
        }

        if (!$constancia->examen || $constancia->examen->resultado !== 'APROBADO') {
            return response()->json([
                'ok' => false,
                'message' => 'No hay examen aprobado.'
            ], 400);
        }

        $ahora = Carbon::now('America/Mexico_City');

        DB::transaction(function () use ($constancia, $ahora) {
            $constancia->update([
                'estatus' => 'ACTIVA',
                'perito_activador_id' => auth()->id(),
                'fecha_activacion' => $ahora,
                'fecha_expiracion' => $ahora->copy()->addDays(10),
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaActivacion::create([
                'constancia_id' => $constancia->id,
                'user_id' => auth()->id(),
                'accion' => 'ACTIVADA',
                'fecha' => $ahora,
                'observaciones' => null,
            ]);
        });

        $constancia->load(['modulo', 'examen', 'peritoActivador']);

        return response()->json([
            'ok' => true,
            'message' => 'Constancia activada.',
            'fecha_expiracion' => $constancia->fecha_expiracion,
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function cancelarAcceso(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        $constancia->update([
            'acceso_examen_token' => null,
            'acceso_examen_expira' => null,
        ]);

        $constancia->load(['modulo', 'examen', 'peritoActivador']);

        return response()->json([
            'ok' => true,
            'message' => 'Acceso cancelado.',
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function accesoQr(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        if (!$constancia->acceso_examen_token) {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no tiene acceso temporal activo.',
            ], 404);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return response()->json([
                'ok' => false,
                'message' => 'El acceso temporal al examen ya expiro.',
            ], 410);
        }

        $png = $this->examenQrPng($constancia);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function crearConstancias(int $moduloId, int $cantidad): array
    {
        $modulo = ConstanciaModulo::findOrFail($moduloId);
        $origen = $modulo->tipo === 'SINIESTROS' ? 'SINIESTROS' : 'DELEGACIONES';
        $prefijo = $modulo->tipo === 'SINIESTROS' ? 'S' : 'D';
        $creadas = [];

        $ultimoNumero = ConstanciaFolio::where('prefijo', $prefijo)
            ->lockForUpdate()
            ->max('numero');

        $numeroInicial = $ultimoNumero ? $ultimoNumero + 1 : 1;

        for ($i = 0; $i < $cantidad; $i++) {
            $numero = $numeroInicial + $i;
            $folio = $prefijo . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

            $constancia = ConstanciaManejo::create([
                'folio' => $folio,
                'folio_qr' => $folio,
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'user_id' => auth()->id(),
                'perito_activador_id' => null,
                'nombre_solicitante' => null,
                'curp' => null,
                'telefono' => null,
                'tipo_licencia' => null,
                'tipo_examen' => null,
                'estatus' => 'IMPRESA_INACTIVA',
                'fecha_impresion' => Carbon::now('America/Mexico_City'),
                'fecha_activacion' => null,
                'fecha_expiracion' => null,
                'pdf_path' => null,
                'qr_token' => Str::uuid()->toString(),
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaFolio::create([
                'prefijo' => $prefijo,
                'numero' => $numero,
                'folio' => $folio,
                'origen' => $origen,
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'constancia_id' => $constancia->id,
                'estatus' => 'ASIGNADO',
            ]);

            $creadas[] = $constancia->id;
        }

        return $creadas;
    }

    private function constanciaPayload(ConstanciaManejo $constancia): array
    {
        $constancia->loadMissing(['modulo', 'examen', 'peritoActivador']);
        $examen = $constancia->examen;
        $examenAprobado = $examen && $examen->resultado === 'APROBADO';
        $tieneAcceso = (bool) ($constancia->acceso_examen_token && $constancia->acceso_examen_expira && !$examenAprobado);

        return [
            'id' => $constancia->id,
            'folio' => $constancia->folio,
            'qr_token' => $constancia->qr_token,
            'estatus' => $constancia->estatus,
            'modulo' => $constancia->modulo->nombre ?? null,
            'delegacion_id' => $constancia->delegacion_id,
            'nombre_solicitante' => $constancia->nombre_solicitante,
            'curp' => $constancia->curp,
            'telefono' => $constancia->telefono,
            'tipo_licencia' => $constancia->tipo_licencia,
            'tipo_examen' => $constancia->tipo_examen,
            'fecha_impresion' => optional($constancia->fecha_impresion)->toISOString(),
            'fecha_activacion' => optional($constancia->fecha_activacion)->toISOString(),
            'fecha_expiracion' => optional($constancia->fecha_expiracion)->toISOString(),
            'acceso_examen_expira' => optional($constancia->acceso_examen_expira)->toISOString(),
            'url_examen' => $tieneAcceso ? $this->examenUrl($constancia) : null,
            'url_examen_qr' => $tieneAcceso ? $this->examenQrUrl($constancia) : null,
            'url_imprimir' => $this->signedPrintUrl([$constancia->id]),
            'qr_examen_base64' => $tieneAcceso ? base64_encode($this->examenQrPng($constancia)) : null,
            'resultado' => $examen->resultado ?? null,
            'examen' => $examen ? [
                'modalidad' => $examen->modalidad,
                'calificacion' => $examen->calificacion,
                'total_preguntas' => $examen->total_preguntas,
                'aciertos' => $examen->aciertos,
                'errores' => $examen->errores,
                'resultado' => $examen->resultado,
                'fecha_examen' => optional($examen->fecha_examen)->toISOString(),
                'observaciones' => $examen->observaciones,
            ] : null,
            'perito_activador' => $constancia->peritoActivador->name ?? null,
            'puede_generar_acceso' => $constancia->estatus === 'IMPRESA_INACTIVA' && !$examenAprobado,
            'puede_capturar_impreso' => $constancia->estatus === 'IMPRESA_INACTIVA' && !$examenAprobado,
            'puede_activar' => $constancia->estatus === 'IMPRESA_INACTIVA'
                && $examenAprobado
                && $constancia->nombre_solicitante
                && $constancia->tipo_licencia
                && $constancia->tipo_examen,
        ];
    }

    private function examenUrl(ConstanciaManejo $constancia): string
    {
        return url('/constancias-manejo/examen/' . $constancia->acceso_examen_token);
    }

    private function examenQrPng(ConstanciaManejo $constancia): string
    {
        $qrCode = QrCode::create($this->examenUrl($constancia))
            ->setSize(520)
            ->setMargin(18)
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setForegroundColor(new Color(15, 23, 42))
            ->setBackgroundColor(new Color(255, 255, 255));

        return (new PngWriter())->write($qrCode)->getString();
    }

    private function examenQrUrl(ConstanciaManejo $constancia): string
    {
        return url('/api/constancias-manejo/' . $constancia->id . '/acceso-qr');
    }

    private function signedPrintUrl(array $ids): string
    {
        return URL::temporarySignedRoute(
            'constancias_manejo.imprimir_lote_firmado',
            Carbon::now('America/Mexico_City')->addMinutes(45),
            ['ids' => implode(',', $ids)]
        );
    }

    private function queryModulosPermitidos()
    {
        $user = auth()->user();
        $query = ConstanciaModulo::where('activo', true)->orderBy('nombre');

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperadmin()) {
            return $query;
        }

        $unidadIds = $this->unidadIdsUsuario($user);
        $delegacionIds = $this->delegacionIdsUsuario($user);
        $puedeSiniestros = in_array(1, $unidadIds, true);
        $puedeDelegacion = in_array(2, $unidadIds, true) && count($delegacionIds) > 0;

        if (!$puedeSiniestros && !$puedeDelegacion) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($puedeSiniestros, $puedeDelegacion, $delegacionIds) {
            if ($puedeSiniestros) {
                $q->orWhere('tipo', 'SINIESTROS');
            }

            if ($puedeDelegacion) {
                $q->orWhere(function ($delegacion) use ($delegacionIds) {
                    $delegacion->where('tipo', 'DELEGACION')
                        ->whereIn('delegacion_id', $delegacionIds);
                });
            }
        });
    }

    private function queryConstanciasPermitidas()
    {
        return ConstanciaManejo::query()
            ->whereIn('modulo_id', $this->queryModulosPermitidos()->pluck('id'));
    }

    private function authorizeConstancia(ConstanciaManejo $constancia): void
    {
        abort_unless(
            $this->queryConstanciasPermitidas()->whereKey($constancia->id)->exists(),
            403,
            'No tienes permiso para ver esta constancia.'
        );
    }

    private function authorizeConstanciasUnidad(): void
    {
        $user = auth()->user();

        abort_if(!$user, 403, 'No autenticado.');

        abort_if(
            $this->queryModulosPermitidos()->count() === 0,
            403,
            'No tienes acceso al modulo de constancias de manejo.'
        );
    }

    private function unidadIdsUsuario($user): array
    {
        $ids = [(int) ($user->unidad_id ?? 0)];

        try {
            $ids = array_merge(
                $ids,
                $user->unidades()->pluck('unidades.id')->map(fn ($id) => (int) $id)->all()
            );
        } catch (\Throwable $e) {
            // La unidad principal basta en instalaciones sin pivote sincronizado.
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function delegacionIdsUsuario($user): array
    {
        $ids = [(int) ($user->delegacion_id ?? 0)];

        try {
            $ids = array_merge(
                $ids,
                DB::table('delegacion_user')
                    ->where('user_id', $user->id)
                    ->pluck('delegacion_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        } catch (\Throwable $e) {
            // La delegacion principal basta en instalaciones sin pivote sincronizado.
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
