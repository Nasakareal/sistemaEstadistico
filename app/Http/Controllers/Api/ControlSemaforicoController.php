<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SemaforoNodo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ControlSemaforicoController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'incluir_inactivos' => ['nullable', 'boolean'],
        ]);

        $query = SemaforoNodo::query()->orderBy('nombre');
        if (! ($validated['incluir_inactivos'] ?? false)) {
            $query->where('activo', true);
        }

        $search = trim((string) ($validated['q'] ?? ''));
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($nodes) use ($escaped) {
                $nodes->where('nombre', 'like', "%{$escaped}%")
                    ->orWhere('ruta', 'like', "%{$escaped}%")
                    ->orWhere('node_id', 'like', "%{$escaped}%")
                    ->orWhere('ubicacion', 'like', "%{$escaped}%")
                    ->orWhere('vialidad_principal', 'like', "%{$escaped}%")
                    ->orWhere('vialidad_transversal', 'like', "%{$escaped}%");
            });
        }

        return response()->json([
            'data' => $query->limit(500)->get()->map(fn (SemaforoNodo $node) => $this->payload($node)),
        ]);
    }

    public function show(SemaforoNodo $semaforoNodo)
    {
        return response()->json(['data' => $this->payload($semaforoNodo)]);
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'node_id' => ['required', 'string', 'max:32'],
            'ruta' => ['required', 'string', 'max:80'],
            'nombre' => ['required', 'string', 'max:160'],
            'ubicacion' => ['nullable', 'string', 'max:200'],
            'vialidad_principal' => ['nullable', 'string', 'max:160'],
            'vialidad_transversal' => ['nullable', 'string', 'max:160'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'configuracion' => ['nullable', 'array'],
            'plan_activo' => ['nullable', 'string', 'max:40'],
            'horario_inicio' => ['nullable', 'date_format:H:i'],
            'horario_fin' => ['nullable', 'date_format:H:i'],
            'horario_estado' => ['nullable', 'string', 'max:30'],
            'estado_operativo' => ['nullable', Rule::in(['configurado', 'online', 'offline', 'sin_confirmar', 'mantenimiento'])],
            'activo' => ['nullable', 'boolean'],
        ]);

        $nodeId = strtoupper(trim($data['node_id']));
        $route = strtoupper(trim($data['ruta']));
        $conflict = SemaforoNodo::query()
            ->where('ruta', $route)
            ->where('node_id', '!=', $nodeId)
            ->first();
        if ($conflict) {
            return response()->json([
                'message' => 'La ruta ya pertenece a otro nodo. Revise la identidad antes de sincronizar.',
            ], 422);
        }

        $node = SemaforoNodo::query()->updateOrCreate(
            ['node_id' => $nodeId],
            array_merge($data, [
                'node_id' => $nodeId,
                'ruta' => $route,
                'nombre' => trim($data['nombre']),
                'estado_operativo' => $data['estado_operativo'] ?? 'online',
                'ultimo_contacto_at' => now(),
                'activo' => $data['activo'] ?? true,
            ])
        );

        return response()->json([
            'message' => 'Crucero sincronizado correctamente.',
            'data' => $this->payload($node),
        ]);
    }

    private function payload(SemaforoNodo $node): array
    {
        $online = $node->ultimo_contacto_at
            ? $node->ultimo_contacto_at->greaterThanOrEqualTo(now()->subMinutes(10))
            : false;

        return [
            'id' => $node->id,
            'node_id' => $node->node_id,
            'ruta' => $node->ruta,
            'nombre' => $node->nombre,
            'ubicacion' => $node->ubicacion,
            'vialidad_principal' => $node->vialidad_principal,
            'vialidad_transversal' => $node->vialidad_transversal,
            'latitud' => $node->latitud,
            'longitud' => $node->longitud,
            'configuracion' => $node->configuracion ?: (object) [],
            'plan_activo' => $node->plan_activo,
            'horario_inicio' => $this->shortTime($node->horario_inicio),
            'horario_fin' => $this->shortTime($node->horario_fin),
            'horario_estado' => $node->horario_estado,
            'estado_operativo' => $node->estado_operativo,
            'online' => $online,
            'ultimo_contacto_at' => $node->ultimo_contacto_at
                ? $node->ultimo_contacto_at->toISOString()
                : null,
            'activo' => (bool) $node->activo,
            'updated_at' => $node->updated_at ? $node->updated_at->toISOString() : null,
        ];
    }

    private function shortTime(?string $value): ?string
    {
        return $value ? substr($value, 0, 5) : null;
    }
}
