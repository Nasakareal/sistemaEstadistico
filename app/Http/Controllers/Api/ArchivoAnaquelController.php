<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivoAnaquel;
use App\Models\ArchivoArea;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoAnaquelController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'area_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $anaquels = ArchivoAnaquel::with('area')
            ->withCount('bloques')
            ->when(isset($filtros['area_id']), fn ($q) => $q->where('area_id', $filtros['area_id']))
            ->when(array_key_exists('activo', $filtros), fn ($q) => $q->where('activo', (bool) $filtros['activo']))
            ->orderBy('area_id')->orderBy('codigo')
            ->paginate((int) ($filtros['per_page'] ?? 30))->withQueryString();

        return \App\Support\ApiPaginationResponse::make($anaquels);
    }

    public function mapa(Request $request)
    {
        $filtros = $request->validate(['area_id' => ['nullable', 'integer', 'min:1']]);
        $areas = ArchivoArea::query()
            ->where('activo', true)
            ->when(isset($filtros['area_id']), fn ($q) => $q->whereKey($filtros['area_id']))
            ->with([
                'anaqueles' => fn ($q) => $q->where('activo', true)->orderBy('codigo'),
                'anaqueles.bloques' => fn ($q) => $q->where('activo', true)->orderBy('fila')->orderBy('columna'),
            ])->orderBy('nombre')->get();

        $bloqueIds = $areas->flatMap(fn ($a) => $a->anaqueles)->flatMap(fn ($a) => $a->bloques)->pluck('id')->all();

        $ocupaciones = ArchivoLibro::whereIn('bloque_actual_id', $bloqueIds)
            ->selectRaw('bloque_actual_id, COUNT(*) as total')
            ->groupBy('bloque_actual_id')->pluck('total', 'bloque_actual_id');

        $reservas = ArchivoLibro::whereIn('bloque_destino_id', $bloqueIds)
            ->where(fn ($q) => $q->whereNull('bloque_actual_id')
                ->orWhereColumn('bloque_actual_id', '<>', 'bloque_destino_id'))
            ->selectRaw('bloque_destino_id, COUNT(*) as total')
            ->groupBy('bloque_destino_id')->pluck('total', 'bloque_destino_id');

        $datos = $areas->map(function ($area) use ($ocupaciones, $reservas) {
            return [
                'id' => $area->id,
                'codigo' => $area->codigo,
                'nombre' => $area->nombre,
                'tipo' => $area->tipo,
                'plano_ancho' => $area->plano_ancho,
                'plano_alto' => $area->plano_alto,
                'anaqueles' => $area->anaqueles->map(function ($anaquel) use ($ocupaciones, $reservas) {
                    return [
                        'id' => $anaquel->id,
                        'codigo' => $anaquel->codigo,
                        'nombre' => $anaquel->nombre,
                        'total_filas' => $anaquel->total_filas,
                        'total_columnas' => $anaquel->total_columnas,
                        'plano_x' => $anaquel->plano_x,
                        'plano_y' => $anaquel->plano_y,
                        'plano_ancho' => $anaquel->plano_ancho,
                        'plano_alto' => $anaquel->plano_alto,
                        'rotacion' => $anaquel->rotacion,
                        'bloques' => $anaquel->bloques->map(function ($bloque) use ($ocupaciones, $reservas) {
                            $ocupados = (int) ($ocupaciones[$bloque->id] ?? 0);
                            $reservados = (int) ($reservas[$bloque->id] ?? 0);
                            return [
                                'id' => $bloque->id,
                                'codigo' => $bloque->codigo,
                                'fila' => $bloque->fila,
                                'columna' => $bloque->columna,
                                'capacidad_libros' => $bloque->capacidad_libros,
                                'ocupados' => $ocupados,
                                'reservados' => $reservados,
                                'disponibles' => max(0, $bloque->capacidad_libros - $ocupados - $reservados),
                                'orden_apilado' => $bloque->orden_apilado,
                                'qr_uuid' => $bloque->qr_uuid,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['data' => $datos]);
    }

    public function show(ArchivoAnaquel $anaquel)
    {
        $anaquel->load(['area', 'bloques' => fn ($q) => $q->orderBy('fila')->orderBy('columna')]);
        return response()->json(['data' => [
            'id' => $anaquel->id,
            'area' => $anaquel->area->only(['id', 'codigo', 'nombre']),
            'codigo' => $anaquel->codigo,
            'nombre' => $anaquel->nombre,
            'total_filas' => $anaquel->total_filas,
            'total_columnas' => $anaquel->total_columnas,
            'plano_x' => $anaquel->plano_x,
            'plano_y' => $anaquel->plano_y,
            'plano_ancho' => $anaquel->plano_ancho,
            'plano_alto' => $anaquel->plano_alto,
            'rotacion' => $anaquel->rotacion,
            'activo' => $anaquel->activo,
            'bloques' => $anaquel->bloques->map(fn ($bloque) => $bloque->only([
                'id', 'codigo', 'fila', 'columna', 'capacidad_libros', 'orden_apilado', 'activo', 'qr_uuid',
            ]))->values(),
        ]]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        $anaquel = ArchivoAnaquel::create($datos);
        return response()->json(['message' => 'Anaquel registrado correctamente.', 'data' => $anaquel], 201);
    }

    public function update(Request $request, ArchivoAnaquel $anaquel)
    {
        $entrada = $request->isMethod('patch')
            ? array_merge($anaquel->only([
                'area_id', 'codigo', 'nombre', 'total_filas', 'total_columnas',
                'plano_x', 'plano_y', 'plano_ancho', 'plano_alto', 'rotacion', 'activo',
            ]), $request->all())
            : $request->all();
        $datos = validator($entrada, $this->reglas($anaquel))->validate();
        if ($anaquel->bloques()->exists() && (int) $anaquel->area_id !== (int) $datos['area_id']) {
            return response()->json(['message' => 'No puedes cambiar de área un anaquel que contiene bloques.'], 422);
        }
        if ($anaquel->bloques()->where('fila', '>', $datos['total_filas'])->exists()
            || $anaquel->bloques()->where('columna', '>', $datos['total_columnas'])->exists()) {
            return response()->json(['message' => 'Las nuevas dimensiones dejarían bloques fuera del anaquel.'], 422);
        }
        if (!(bool) $datos['activo'] && $anaquel->bloques()->exists()) {
            return response()->json(['message' => 'No puedes desactivar un anaquel que tiene bloques.'], 422);
        }
        $anaquel->update($datos);
        return response()->json(['message' => 'Anaquel actualizado correctamente.', 'data' => $anaquel->fresh()]);
    }

    private function reglas(?ArchivoAnaquel $anaquel = null): array
    {
        $areaId = request('area_id', $anaquel?->area_id);

        return [
            'area_id' => ['required', Rule::exists('archivo_areas', 'id')->where('activo', true)],
            'codigo' => ['required', 'string', 'max:30', Rule::unique('archivo_anaqueles', 'codigo')
                ->where('area_id', $areaId)->ignore($anaquel?->id)],
            'nombre' => ['nullable', 'string', 'max:150'],
            'total_filas' => ['required', 'integer', 'between:1,100'],
            'total_columnas' => ['required', 'integer', 'between:1,100'],
            'plano_x' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_y' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_ancho' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_alto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'rotacion' => ['required', 'integer', 'between:0,359'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
