<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\Lesionado;
use Illuminate\Http\Request;

class LesionadoController extends Controller
{
    /**
     * GET /api/hechos/{hecho}/lesionados
     */
    public function index(Hechos $hecho)
    {
        $lesionados = $hecho->lesionados()->orderByDesc('id')->get();

        return response()->json([
            'data' => $lesionados,
        ]);
    }

    /**
     * POST /api/hechos/{hecho}/lesionados
     */
    public function store(Request $request, Hechos $hecho)
    {
        $validated = $this->validatePayload($request);

        $lesionado = $hecho->lesionados()->create($validated);

        return response()->json([
            'message' => 'Lesionado agregado correctamente.',
            'data' => $lesionado,
        ], 201);
    }

    /**
     * GET /api/hechos/{hecho}/lesionados/{lesionado}
     */
    public function show(Hechos $hecho, Lesionado $lesionado)
    {
        $this->ensureBelongsToHecho($hecho, $lesionado);

        return response()->json([
            'data' => $lesionado,
        ]);
    }

    /**
     * PUT /api/hechos/{hecho}/lesionados/{lesionado}
     */
    public function update(Request $request, Hechos $hecho, Lesionado $lesionado)
    {
        $this->ensureBelongsToHecho($hecho, $lesionado);

        $validated = $this->validatePayload($request);

        $lesionado->update($validated);

        return response()->json([
            'message' => 'Lesionado actualizado correctamente.',
            'data' => $lesionado->fresh(),
        ]);
    }

    /**
     * DELETE /api/hechos/{hecho}/lesionados/{lesionado}
     */
    public function destroy(Hechos $hecho, Lesionado $lesionado)
    {
        $this->ensureBelongsToHecho($hecho, $lesionado);

        $lesionado->delete();

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

        // Asegura booleanos consistentes aunque Flutter mande "true"/"false"/1/0/"1"/"0"
        $validated['hospitalizado'] = (bool)($validated['hospitalizado'] ?? false);
        $validated['atencion_en_sitio'] = (bool)($validated['atencion_en_sitio'] ?? false);

        return $validated;
    }
}
