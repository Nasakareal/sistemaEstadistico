<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModuloExamenDiario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuloExamenDiarioController extends Controller
{
    private const TIPOS_EXAMEN = [
        'servicio_publico',
        'automovilista',
        'chofer',
        'motociclista',
        'permiso',
    ];

    private const CONTEOS = [
        'hombres',
        'mujeres',
        'aprobados',
        'reprobados',
    ];

    public function index(Request $request)
    {
        $query = ModuloExamenDiario::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->query('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('modulo_nombre', 'like', "%{$buscar}%")
                    ->orWhere('folios', 'like', "%{$buscar}%")
                    ->orWhere('informado_por', 'like', "%{$buscar}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 50), 100));
        $page = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => $page->getCollection()
                ->map(fn (ModuloExamenDiario $registro) => $this->payload($registro))
                ->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $registro = ModuloExamenDiario::create($data);

        return response()->json([
            'ok' => true,
            'message' => 'Registro guardado correctamente.',
            'data' => $this->payload($registro),
        ], 201);
    }

    public function show(ModuloExamenDiario $registro)
    {
        return response()->json([
            'ok' => true,
            'data' => $this->payload($registro),
        ]);
    }

    public function update(Request $request, ModuloExamenDiario $registro)
    {
        $data = $this->validatedData($request, $registro);
        $registro->update($data);

        return response()->json([
            'ok' => true,
            'message' => 'Registro actualizado correctamente.',
            'data' => $this->payload($registro->fresh()),
        ]);
    }

    public function destroy(ModuloExamenDiario $registro)
    {
        $registro->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request, ?ModuloExamenDiario $registro = null): array
    {
        $rules = [
            'fecha' => ['required', 'date'],
            'modulo_nombre' => [
                'required',
                'string',
                'max:180',
                Rule::unique('modulo_examenes_diarios', 'modulo_nombre')
                    ->where(fn ($query) => $query->where('fecha', $request->input('fecha')))
                    ->ignore($registro ? $registro->id : null),
            ],
            'folios' => ['nullable', 'string', 'max:255'],
            'informado_por' => ['nullable', 'string', 'max:180'],
        ];

        foreach (array_merge(self::TIPOS_EXAMEN, self::CONTEOS) as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0'];
        }

        $data = $request->validate($rules, [
            'modulo_nombre.unique' => 'Ya existe un registro para ese modulo en la fecha seleccionada.',
        ]);

        foreach (self::TIPOS_EXAMEN as $field) {
            $data[$field] = (int) ($data[$field] ?? 0);
        }

        foreach (self::CONTEOS as $field) {
            $data[$field] = (int) ($data[$field] ?? 0);
        }

        $data['total'] = array_sum(array_map(fn ($field) => $data[$field], self::TIPOS_EXAMEN));

        if (trim((string) ($data['informado_por'] ?? '')) === '') {
            $data['informado_por'] = optional($request->user())->name;
        }

        return $data;
    }

    private function payload(ModuloExamenDiario $registro): array
    {
        return [
            'id' => $registro->id,
            'fecha' => optional($registro->fecha)->format('Y-m-d'),
            'modulo_nombre' => $registro->modulo_nombre,
            'servicio_publico' => (int) $registro->servicio_publico,
            'automovilista' => (int) $registro->automovilista,
            'chofer' => (int) $registro->chofer,
            'motociclista' => (int) $registro->motociclista,
            'permiso' => (int) $registro->permiso,
            'total' => (int) $registro->total,
            'hombres' => (int) $registro->hombres,
            'mujeres' => (int) $registro->mujeres,
            'aprobados' => (int) $registro->aprobados,
            'reprobados' => (int) $registro->reprobados,
            'folios' => $registro->folios,
            'informado_por' => $registro->informado_por,
            'created_at' => optional($registro->created_at)->toISOString(),
            'updated_at' => optional($registro->updated_at)->toISOString(),
        ];
    }
}
