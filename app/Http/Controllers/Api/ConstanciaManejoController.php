<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaExamen;
use Carbon\Carbon;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConstanciaManejoController extends Controller
{
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

        return response()->json([
            'ok' => true,
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function show(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();

        $constancia->load(['modulo', 'examen', 'peritoActivador']);

        return response()->json([
            'ok' => true,
            'constancia' => $this->constanciaPayload($constancia),
        ]);
    }

    public function generarAcceso(ConstanciaManejo $constancia)
    {
        $this->authorizeConstanciasUnidad();

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no está disponible.'
            ], 400);
        }

        $constancia->update([
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

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no está disponible.'
            ], 400);
        }

        $request->validate([
            'nombre_solicitante' => 'required|string|max:255',
            'curp' => 'nullable|string|max:18',
            'telefono' => 'nullable|string|max:20',
            'tipo_licencia' => 'required|in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO',
            'calificacion' => 'required|numeric|min:0|max:100',
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

        $resultado = $request->calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

        DB::transaction(function () use ($request, $constancia, $resultado) {
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
                    'calificacion' => $request->calificacion,
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

        $constancia->load('examen');

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no está disponible.'
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

        if (!$constancia->acceso_examen_token) {
            return response()->json([
                'ok' => false,
                'message' => 'La constancia no tiene acceso temporal activo.',
            ], 404);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return response()->json([
                'ok' => false,
                'message' => 'El acceso temporal al examen ya expiró.',
            ], 410);
        }

        $qrCode = QrCode::create($this->examenUrl($constancia))
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

    private function constanciaPayload(ConstanciaManejo $constancia): array
    {
        $constancia->loadMissing(['modulo', 'examen', 'peritoActivador']);
        $examen = $constancia->examen;
        $tieneAcceso = (bool) ($constancia->acceso_examen_token && $constancia->acceso_examen_expira);
        $examenAprobado = $examen && $examen->resultado === 'APROBADO';

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
            'puede_generar_acceso' => $constancia->estatus === 'IMPRESA_INACTIVA',
            'puede_capturar_impreso' => $constancia->estatus === 'IMPRESA_INACTIVA',
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

    private function examenQrUrl(ConstanciaManejo $constancia): string
    {
        return url('/api/constancias-manejo/' . $constancia->id . '/acceso-qr');
    }

    private function authorizeConstanciasUnidad(): void
    {
        $user = auth()->user();

        abort_if(!$user, 403, 'No autenticado.');

        if ($user->isSuperadmin()) {
            return;
        }

        $unidadIds = [(int) ($user->unidad_id ?? 0)];

        try {
            $unidadIds = array_merge(
                $unidadIds,
                $user->unidades()->pluck('unidades.id')->map(fn ($id) => (int) $id)->all()
            );
        } catch (\Throwable $e) {
            // La unidad principal basta en instalaciones sin pivote sincronizado.
        }

        if (count(array_intersect([1, 2], array_unique($unidadIds))) === 0) {
            abort(403, 'No tienes acceso al módulo de constancias de manejo.');
        }
    }
}
