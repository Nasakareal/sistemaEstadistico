<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\Lesionado;
use App\Services\DelegacionesWhatsAppAlertService;
use App\Support\HechoAccess;
use Illuminate\Http\Request;

class LesionadoController extends Controller
{
    public function index(Hechos $hecho)
    {
        if (!HechoAccess::canView(request()->user(), $hecho)) {
            return response()->json([
                'message' => 'No tienes permiso para consultar este hecho.',
            ], 403);
        }

        $lesionados = $hecho->lesionados()->orderByDesc('id')->get();

        return response()->json([
            'data' => $lesionados,
        ]);
    }

    public function store(Request $request, ?Hechos $hecho = null)
    {
        if (!$hecho) {
            if ($request->filled('hecho_client_uuid')) {
                $hecho = Hechos::where('client_uuid', $request->input('hecho_client_uuid'))->first();
            } elseif ($request->filled('hecho_id')) {
                $hecho = Hechos::find($request->input('hecho_id'));
            }
        }

        if (!$hecho) {
            return response()->json([
                'message' => 'No existe un hecho válido para relacionar el lesionado.',
            ], 404);
        }

        if (!HechoAccess::canEdit($request->user(), $hecho)) {
            return response()->json([
                'message' => 'No tienes permiso para editar este hecho.',
            ], 403);
        }

        $validated = $this->validatePayload($request);

        if (!empty($validated['client_uuid'])) {
            $lesionadoExistente = Lesionado::where('client_uuid', $validated['client_uuid'])->first();

            if ($lesionadoExistente) {
                $hecho->actualizarEstadoCaptura();

                return response()->json([
                    'message' => 'Lesionado ya existente.',
                    'created' => false,
                    'data' => $lesionadoExistente,
                    'meta' => [
                        'id' => $lesionadoExistente->id,
                        'client_uuid' => $lesionadoExistente->client_uuid,
                    ],
                ], 200);
            }
        }

        $validated['hecho_id'] = $hecho->id;

        $lesionado = Lesionado::create($validated);

        $hecho->actualizarEstadoCaptura();

        app(DelegacionesWhatsAppAlertService::class)->notificarFallecido($lesionado);

        return response()->json([
            'message' => 'Lesionado agregado correctamente.',
            'created' => true,
            'data' => $lesionado,
            'meta' => [
                'id' => $lesionado->id,
                'client_uuid' => $lesionado->client_uuid,
            ],
        ], 201);
    }

    public function show(Hechos $hecho, Lesionado $lesionado)
    {
        if (!HechoAccess::canView(request()->user(), $hecho)) {
            return response()->json([
                'message' => 'No tienes permiso para consultar este hecho.',
            ], 403);
        }

        $this->ensureBelongsToHecho($hecho, $lesionado);

        return response()->json([
            'data' => $lesionado,
        ]);
    }

    public function update(Request $request, Hechos $hecho, Lesionado $lesionado)
    {
        if (!HechoAccess::canEdit($request->user(), $hecho)) {
            return response()->json([
                'message' => 'No tienes permiso para editar este hecho.',
            ], 403);
        }

        $this->ensureBelongsToHecho($hecho, $lesionado);

        $validated = $this->validatePayload($request);
        $alertService = app(DelegacionesWhatsAppAlertService::class);
        $eraFallecido = $alertService->esFallecido($lesionado->tipo_lesion);

        $lesionado->update($validated);

        $hecho->actualizarEstadoCaptura();

        if (!$eraFallecido) {
            $alertService->notificarFallecido($lesionado->refresh());
        }

        return response()->json([
            'message' => 'Lesionado actualizado correctamente.',
            'data' => $lesionado->fresh(),
        ]);
    }

    public function destroy(Hechos $hecho, Lesionado $lesionado)
    {
        if (!HechoAccess::canEdit(request()->user(), $hecho)) {
            return response()->json([
                'message' => 'No tienes permiso para editar este hecho.',
            ], 403);
        }

        $this->ensureBelongsToHecho($hecho, $lesionado);

        $lesionado->delete();

        $hecho->actualizarEstadoCaptura();

        return response()->json([
            'message' => 'Lesionado eliminado correctamente.',
        ]);
    }

    /* ===================== HELPERS ===================== */

    private function ensureBelongsToHecho(Hechos $hecho, Lesionado $lesionado): void
    {
        if ((int)$lesionado->hecho_id !== (int)$hecho->id) {
            abort(404, 'El lesionado no pertenece a este hecho.');
        }
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'client_uuid' => 'nullable|string|max:36',
            'hecho_client_uuid' => 'nullable|string|max:36',
            'hecho_id' => 'nullable|integer',

            'nombre' => 'required|string|max:255',
            'edad' => 'nullable|integer|min:0',
            'sexo' => 'nullable|string|in:Masculino,Femenino,Otro',
            'tipo_lesion' => 'required|string|in:Leve,Moderada,Grave,Fallecido',
            'hospitalizado' => 'required|boolean',
            'hospital' => 'nullable|string|max:255',
            'atencion_en_sitio' => 'required|boolean',
            'ambulancia' => 'nullable|string|max:255',
            'paramedico' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $validated['hospitalizado'] = (bool)($validated['hospitalizado'] ?? false);
        $validated['atencion_en_sitio'] = (bool)($validated['atencion_en_sitio'] ?? false);

        return $validated;
    }
}
