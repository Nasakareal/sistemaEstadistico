<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaExamen;
use App\Models\ConstanciaExamenSolicitud;
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
            'message' => count($ids) . ' constancias generadas como inactivas.',
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

    public function buscarExamenEscritoPorQr($token)
    {
        $this->authorizeConstanciasUnidad();

        $constancia = ConstanciaManejo::with(['modulo', 'examen', 'peritoActivador'])
            ->where('acceso_examen_token', $token)
            ->where('tipo_examen', 'IMPRESO')
            ->first();

        if (!$constancia) {
            return response()->json([
                'ok' => false,
                'message' => 'Examen escrito no encontrado.'
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

    public function storeExamen(Request $request)
    {
        $this->authorizeConstanciasUnidad();

        $request->merge([
            'sexo' => $this->normalizarSexo($request->input('sexo')),
            'modalidad' => strtoupper((string) $request->input('modalidad', 'LINEA')),
        ]);

        $request->validate([
            'modulo_id' => ['required', 'integer', 'exists:constancia_modulos,id'],
            'nombre_solicitante' => ['required', 'string', 'max:255'],
            'sexo' => ['required', 'in:HOMBRE,MUJER'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['required', 'in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO'],
            'modalidad' => ['required', 'in:LINEA,IMPRESO'],
        ]);

        $modulo = $this->queryModulosPermitidos()
            ->where('id', $request->input('modulo_id'))
            ->first();

        if (!$modulo) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para generar examenes en este modulo.',
            ], 403);
        }

        $modalidad = $request->input('modalidad');
        $solicitud = DB::transaction(function () use ($request, $modulo, $modalidad) {
            $idPreview = (int) (ConstanciaExamenSolicitud::lockForUpdate()->max('id') ?? 0) + 1;

            return ConstanciaExamenSolicitud::create([
                'folio_examen' => 'EX-' . str_pad((string) $idPreview, 6, '0', STR_PAD_LEFT),
                'token' => Str::random(60),
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'user_id' => auth()->id(),
                'constancia_id' => null,
                'nombre_solicitante' => mb_strtoupper($request->input('nombre_solicitante'), 'UTF-8'),
                'sexo' => $request->input('sexo'),
                'curp' => $request->filled('curp') ? mb_strtoupper($request->input('curp'), 'UTF-8') : null,
                'telefono' => $request->input('telefono'),
                'tipo_licencia' => $request->input('tipo_licencia'),
                'modalidad' => $modalidad,
                'estatus' => 'PENDIENTE',
                'token_expira' => $modalidad === 'LINEA'
                    ? Carbon::now('America/Mexico_City')->addMinutes(30)
                    : Carbon::now('America/Mexico_City')->endOfDay(),
            ]);
        });

        $solicitud->load(['modulo', 'constancia']);

        return response()->json([
            'ok' => true,
            'message' => $modalidad === 'LINEA'
                ? 'Acceso de examen en linea generado.'
                : 'Examen escrito generado.',
            'examen' => $this->examenSolicitudPayload($solicitud),
        ], 201);
    }

    public function buscarExamenPorQr($token)
    {
        $this->authorizeConstanciasUnidad();

        $solicitud = ConstanciaExamenSolicitud::with(['modulo', 'constancia.examen'])
            ->where('token', $token)
            ->first();

        if (!$solicitud) {
            return response()->json([
                'ok' => false,
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $this->authorizeExamenSolicitud($solicitud);

        return response()->json([
            'ok' => true,
            'examen' => $this->examenSolicitudPayload($solicitud),
        ]);
    }

    public function examenSolicitudQr(ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeExamenSolicitud($solicitud);

        if (!$solicitud->token || !$solicitud->token_expira || Carbon::now('America/Mexico_City')->greaterThan($solicitud->token_expira)) {
            return response()->json([
                'ok' => false,
                'message' => 'El acceso al examen ya expiro.',
            ], 410);
        }

        return response($this->qrPng($this->examenSolicitudUrl($solicitud)), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function capturarExamenSolicitudImpresa(Request $request, ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeExamenSolicitud($solicitud);

        if ($solicitud->modalidad !== 'IMPRESO') {
            return response()->json([
                'ok' => false,
                'message' => 'Este registro no corresponde a un examen escrito.',
            ], 400);
        }

        if ($solicitud->constancia_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este examen ya fue asignado a una constancia.',
            ], 400);
        }

        $request->validate([
            'total_preguntas' => ['required', 'integer', 'min:1'],
            'aciertos' => ['required', 'integer', 'min:0'],
            'errores' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if ((int) $request->input('aciertos') + (int) $request->input('errores') !== (int) $request->input('total_preguntas')) {
            return response()->json([
                'ok' => false,
                'message' => 'Aciertos y errores no coinciden.',
            ], 400);
        }

        $total = (int) $request->input('total_preguntas');
        $aciertos = (int) $request->input('aciertos');
        $errores = (int) $request->input('errores');
        $calificacion = round(($aciertos / $total) * 100, 2);

        $solicitud->update([
            'calificacion' => $calificacion,
            'total_preguntas' => $total,
            'aciertos' => $aciertos,
            'errores' => $errores,
            'estatus' => $calificacion >= 80 ? 'APROBADO' : 'REPROBADO',
            'fecha_examen' => Carbon::now('America/Mexico_City'),
            'observaciones' => $request->input('observaciones'),
        ]);

        $solicitud->load(['modulo', 'constancia']);

        return response()->json([
            'ok' => true,
            'message' => 'Examen escrito validado.',
            'examen' => $this->examenSolicitudPayload($solicitud),
        ]);
    }

    public function activarConExamen(Request $request, ConstanciaExamenSolicitud $solicitud)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeExamenSolicitud($solicitud);

        if ($solicitud->estatus !== 'APROBADO') {
            return response()->json([
                'ok' => false,
                'message' => 'Solo se puede activar una constancia con examen aprobado.',
            ], 400);
        }

        if ($solicitud->constancia_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Este examen ya fue asignado a una constancia.',
            ], 400);
        }

        $request->validate([
            'constancia_id' => ['nullable', 'integer', 'exists:constancias_manejo,id'],
            'constancia_qr' => ['nullable', 'string', 'max:500'],
        ]);

        $constancia = null;
        if ($request->filled('constancia_id')) {
            $constancia = ConstanciaManejo::find((int) $request->input('constancia_id'));
        }

        if (!$constancia && $request->filled('constancia_qr')) {
            $token = $this->cleanQrToken((string) $request->input('constancia_qr'));
            $constancia = ConstanciaManejo::where('qr_token', $token)->first();
        }

        if (!$constancia) {
            return response()->json([
                'ok' => false,
                'message' => 'Constancia impresa no encontrada.',
            ], 404);
        }

        $this->authorizeConstancia($constancia);

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia impresa no esta inactiva.',
            ], 400);
        }

        $ahora = Carbon::now('America/Mexico_City');

        DB::transaction(function () use ($constancia, $solicitud, $ahora) {
            $constancia->update([
                'nombre_solicitante' => $solicitud->nombre_solicitante,
                'sexo' => $solicitud->sexo,
                'curp' => $solicitud->curp,
                'telefono' => $solicitud->telefono,
                'tipo_licencia' => $solicitud->tipo_licencia,
                'tipo_examen' => $solicitud->modalidad,
                'estatus' => 'ACTIVA',
                'perito_activador_id' => auth()->id(),
                'fecha_activacion' => $ahora,
                'fecha_expiracion' => $ahora->copy()->addDays(10),
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaExamen::updateOrCreate(
                ['constancia_id' => $constancia->id],
                [
                    'modalidad' => $solicitud->modalidad,
                    'calificacion' => $solicitud->calificacion,
                    'total_preguntas' => $solicitud->total_preguntas,
                    'aciertos' => $solicitud->aciertos,
                    'errores' => $solicitud->errores,
                    'resultado' => 'APROBADO',
                    'capturado_por' => auth()->id(),
                    'fecha_examen' => $solicitud->fecha_examen ?: $ahora,
                    'observaciones' => $solicitud->observaciones,
                ]
            );

            $solicitud->update([
                'constancia_id' => $constancia->id,
            ]);

            ConstanciaActivacion::create([
                'constancia_id' => $constancia->id,
                'user_id' => auth()->id(),
                'accion' => 'ACTIVADA',
                'fecha' => $ahora,
                'observaciones' => 'Activada con examen ' . $solicitud->folio_examen,
            ]);
        });

        $constancia->load(['modulo', 'examen', 'peritoActivador']);
        $solicitud->load(['modulo', 'constancia']);

        return response()->json([
            'ok' => true,
            'message' => 'Constancia activada.',
            'constancia' => $this->constanciaPayload($constancia),
            'examen' => $this->examenSolicitudPayload($solicitud),
        ]);
    }

    public function generarAcceso(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        return response()->json([
            'ok' => false,
            'message' => 'Los examenes se generan aparte. Usa POST /api/constancias-manejo/examenes y despues activa con /api/constancias-manejo/examenes/{id}/activar-constancia.',
        ], 409);
    }

    public function generarExamenEscrito(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        return response()->json([
            'ok' => false,
            'message' => 'Los examenes escritos se generan aparte. Usa POST /api/constancias-manejo/examenes con modalidad IMPRESO.',
        ], 409);
    }

    public function capturarImpreso(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        return response()->json([
            'ok' => false,
            'message' => 'Captura el resultado en /api/constancias-manejo/examenes/{id}/capturar-impreso y activa despues con una constancia impresa.',
        ], 409);
    }

    public function activar(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        $constancia->load('examen');

        if (!$this->estaDisponibleParaExamen($constancia)) {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no esta disponible.'
            ], 400);
        }

        if (!$constancia->nombre_solicitante || !$constancia->sexo || !$constancia->tipo_licencia || !$constancia->tipo_examen) {
            return response()->json([
                'ok' => false,
                'message' => 'Faltan datos del solicitante, sexo, tipo de licencia o tipo de examen.'
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

        if (!$constancia->acceso_examen_token || $constancia->tipo_examen === 'IMPRESO') {
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

    public function examenEscritoQr(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();
        $this->authorizeConstancia($constancia);

        if (!$constancia->acceso_examen_token || $constancia->tipo_examen !== 'IMPRESO') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no tiene examen escrito activo.',
            ], 404);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return response()->json([
                'ok' => false,
                'message' => 'El QR del examen escrito ya expiro.',
            ], 410);
        }

        $png = $this->qrPng($this->examenEscritoUrl($constancia));

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
                'sexo' => null,
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
        $tieneAcceso = (bool) (
            $constancia->tipo_examen === 'LINEA'
            && $constancia->acceso_examen_token
            && $constancia->acceso_examen_expira
            && !$examenAprobado
        );
        $tieneExamenEscrito = (bool) (
            $constancia->tipo_examen === 'IMPRESO'
            && $constancia->acceso_examen_token
            && $constancia->acceso_examen_expira
            && !$examenAprobado
        );
        $estaPendiente = $this->estaDisponibleParaExamen($constancia);

        return [
            'id' => $constancia->id,
            'folio' => $constancia->folio,
            'qr_token' => $constancia->qr_token,
            'estatus' => $constancia->estatus,
            'modulo' => $constancia->modulo->nombre ?? null,
            'delegacion_id' => $constancia->delegacion_id,
            'nombre_solicitante' => $constancia->nombre_solicitante,
            'sexo' => $constancia->sexo,
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
            'url_examen_escrito' => $tieneExamenEscrito ? $this->examenEscritoUrl($constancia) : null,
            'url_examen_escrito_qr' => $tieneExamenEscrito ? $this->examenEscritoQrUrl($constancia) : null,
            'url_imprimir' => $constancia->estatus !== 'CANCELADA' ? $this->signedPrintUrl([$constancia->id]) : null,
            'qr_examen_base64' => $tieneAcceso ? base64_encode($this->examenQrPng($constancia)) : null,
            'qr_examen_escrito_base64' => $tieneExamenEscrito ? base64_encode($this->qrPng($this->examenEscritoUrl($constancia))) : null,
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
            'puede_generar_acceso' => $estaPendiente && !$examenAprobado,
            'puede_generar_examen_escrito' => $estaPendiente && !$examenAprobado,
            'puede_capturar_impreso' => $estaPendiente && !$examenAprobado && $constancia->tipo_examen === 'IMPRESO',
            'puede_imprimir' => $constancia->estatus !== 'CANCELADA',
            'puede_activar' => $estaPendiente
                && $examenAprobado
                && $constancia->nombre_solicitante
                && $constancia->sexo
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
        return $this->qrPng($this->examenUrl($constancia));
    }

    private function qrPng(string $url): string
    {
        $qrCode = QrCode::create($url)
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

    private function examenEscritoUrl(ConstanciaManejo $constancia): string
    {
        return url('/constancias-manejo/examen-escrito/' . $constancia->acceso_examen_token);
    }

    private function examenEscritoQrUrl(ConstanciaManejo $constancia): string
    {
        return url('/api/constancias-manejo/' . $constancia->id . '/examen-escrito-qr');
    }

    private function examenSolicitudPayload(ConstanciaExamenSolicitud $solicitud): array
    {
        $solicitud->loadMissing(['modulo', 'constancia']);
        $tokenVigente = (bool) (
            $solicitud->token
            && $solicitud->token_expira
            && Carbon::now('America/Mexico_City')->lessThanOrEqualTo($solicitud->token_expira)
            && !$solicitud->constancia_id
        );

        return [
            'id' => $solicitud->id,
            'folio_examen' => $solicitud->folio_examen,
            'token' => $solicitud->token,
            'modulo_id' => $solicitud->modulo_id,
            'modulo' => $solicitud->modulo->nombre ?? null,
            'delegacion_id' => $solicitud->delegacion_id,
            'constancia_id' => $solicitud->constancia_id,
            'constancia_folio' => $solicitud->constancia->folio ?? null,
            'nombre_solicitante' => $solicitud->nombre_solicitante,
            'sexo' => $solicitud->sexo,
            'curp' => $solicitud->curp,
            'telefono' => $solicitud->telefono,
            'tipo_licencia' => $solicitud->tipo_licencia,
            'modalidad' => $solicitud->modalidad,
            'estatus' => $solicitud->estatus,
            'calificacion' => $solicitud->calificacion,
            'total_preguntas' => $solicitud->total_preguntas,
            'aciertos' => $solicitud->aciertos,
            'errores' => $solicitud->errores,
            'fecha_examen' => optional($solicitud->fecha_examen)->toISOString(),
            'token_expira' => optional($solicitud->token_expira)->toISOString(),
            'observaciones' => $solicitud->observaciones,
            'url_examen' => $tokenVigente ? $this->examenSolicitudUrl($solicitud) : null,
            'url_examen_qr' => $tokenVigente ? $this->examenSolicitudQrUrl($solicitud) : null,
            'qr_examen_base64' => $tokenVigente ? base64_encode($this->qrPng($this->examenSolicitudUrl($solicitud))) : null,
            'puede_capturar_impreso' => $solicitud->modalidad === 'IMPRESO' && !$solicitud->constancia_id,
            'puede_activar_constancia' => $solicitud->estatus === 'APROBADO' && !$solicitud->constancia_id,
        ];
    }

    private function examenSolicitudUrl(ConstanciaExamenSolicitud $solicitud): string
    {
        $path = $solicitud->modalidad === 'IMPRESO'
            ? '/constancias-manejo/examen-escrito/'
            : '/constancias-manejo/examen/';

        return url($path . $solicitud->token);
    }

    private function examenSolicitudQrUrl(ConstanciaExamenSolicitud $solicitud): string
    {
        return url('/api/constancias-manejo/examenes/' . $solicitud->id . '/qr');
    }

    private function cleanQrToken(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $parts = array_values(array_filter(explode('/', trim($path, '/'))));
            if (count($parts) > 0) {
                return trim(urldecode(end($parts)));
            }
        }

        if (strpos($value, ':') !== false) {
            return trim(substr($value, strrpos($value, ':') + 1));
        }

        return $value;
    }

    private function normalizarSexo($sexo): ?string
    {
        $value = mb_strtoupper(trim((string) $sexo), 'UTF-8');

        if (in_array($value, ['H', 'HOMBRE', 'MASCULINO'], true)) {
            return 'HOMBRE';
        }

        if (in_array($value, ['M', 'MUJER', 'FEMENINO'], true)) {
            return 'MUJER';
        }

        return $value ?: null;
    }

    private function signedPrintUrl(array $ids): string
    {
        return URL::temporarySignedRoute(
            'constancias_manejo.imprimir_lote_firmado',
            Carbon::now('America/Mexico_City')->addMinutes(45),
            ['ids' => implode(',', $ids)]
        );
    }

    private function estaDisponibleParaExamen(ConstanciaManejo $constancia): bool
    {
        return in_array($constancia->estatus, ['IMPRESA_INACTIVA', 'PENDIENTE_EXAMEN'], true);
    }

    private function authorizeExamenSolicitud(ConstanciaExamenSolicitud $solicitud): void
    {
        abort_unless(
            $this->queryModulosPermitidos()->whereKey($solicitud->modulo_id)->exists(),
            403,
            'No tienes permiso para ver este examen.'
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
        return array_values(array_filter([(int) ($user->unidad_id ?? 0)]));
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
