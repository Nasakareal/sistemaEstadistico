<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivoArea;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoAreaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'activo' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $areas = ArchivoArea::query()
            ->withCount('anaqueles')
            ->when(!empty($filtros['q']), fn ($q) => $q->where(function ($sub) use ($filtros) {
                $sub->where('nombre', 'like', '%' . $filtros['q'] . '%')
                    ->orWhere('codigo', 'like', '%' . $filtros['q'] . '%');
            }))
            ->when(array_key_exists('activo', $filtros), fn ($q) => $q->where('activo', (bool) $filtros['activo']))
            ->orderBy('nombre')
            ->paginate((int) ($filtros['per_page'] ?? 30))
            ->withQueryString();

        return \App\Support\ApiPaginationResponse::make($areas);
    }

    public function show(ArchivoArea $area)
    {
        $area->load(['anaqueles' => fn ($q) => $q->orderBy('codigo')]);

        return response()->json([
            'data' => [
                'id' => $area->id,
                'codigo' => $area->codigo,
                'nombre' => $area->nombre,
                'tipo' => $area->tipo,
                'descripcion' => $area->descripcion,
                'plano_ancho' => $area->plano_ancho,
                'plano_alto' => $area->plano_alto,
                'activo' => $area->activo,
                'anaqueles' => $area->anaqueles->map(fn ($a) => [
                    'id' => $a->id,
                    'codigo' => $a->codigo,
                    'nombre' => $a->nombre,
                    'total_filas' => $a->total_filas,
                    'total_columnas' => $a->total_columnas,
                    'activo' => $a->activo,
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        $area = ArchivoArea::create($datos);

        return response()->json(['message' => 'Área registrada correctamente.', 'data' => $area], 201);
    }

    public function update(Request $request, ArchivoArea $area)
    {
        $entrada = $request->isMethod('patch')
            ? array_merge($area->only([
                'codigo', 'nombre', 'tipo', 'descripcion', 'plano_ancho', 'plano_alto', 'activo',
            ]), $request->all())
            : $request->all();
        $datos = validator($entrada, $this->reglas($area))->validate();

        if (!(bool) $datos['activo'] && ($area->anaqueles()->exists() || ArchivoLibro::where('area_actual_id', $area->id)->exists())) {
            return response()->json(['message' => 'El área contiene anaqueles o libros.', 'errors' => [
                'activo' => ['No puedes desactivar un área que todavía contiene anaqueles o libros.'],
            ]], 422);
        }

        $area->update($datos);

        return response()->json(['message' => 'Área actualizada correctamente.', 'data' => $area->fresh()]);
    }

    private function reglas(?ArchivoArea $area = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('archivo_areas', 'codigo')->ignore($area?->id)],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(['sala', 'mesa', 'otro'])],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'plano_ancho' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_alto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
