<?php

namespace App\Http\Controllers;

use App\Models\IncidenciaTipo;
use App\Models\Personal;
use App\Models\PersonalIncidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonalIncidenciaController extends Controller
{
    private const TIPOS_INCIDENCIA_FALLBACK = [
        'VACACIONES' => 1,
        'INCAPACIDAD' => 2,
        'PERMISO' => 3,
        'FALTA' => 4,
        'COMISION' => 5,
        'SUSPENSION' => 6,
        'OTRO' => 7,
        'SERVICIO' => 8,
    ];

    public function store(Request $request, Personal $personal)
    {
        $validated = $request->validate([
            'tipo' => 'required|string|max:60',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'folio' => 'nullable|string|max:60',
            'motivo' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000',
            'documento_id' => 'nullable|integer',
        ]);

        try {
            $tipo = $this->resolverTipoIncidencia($validated['tipo']);

            if (!$tipo) {
                return redirect()->back()
                    ->withErrors(['tipo' => 'Tipo de incidencia no válido.'])
                    ->withInput();
            }

            $inicio = $validated['fecha_inicio'];
            $fin = $validated['fecha_fin'] ?? null;

            $traslapa = PersonalIncidencia::query()
                ->where('personal_id', $personal->id)
                ->where(function ($q) use ($inicio, $fin) {
                    $q->where(function ($qq) use ($inicio, $fin) {
                        $qq->whereNull('fecha_fin')
                           ->where('fecha_inicio', '<=', $fin ?? $inicio);
                    })->orWhere(function ($qq) use ($inicio, $fin) {
                        $qq->whereNotNull('fecha_fin')
                           ->where('fecha_inicio', '<=', $fin ?? $inicio)
                           ->where('fecha_fin', '>=', $inicio);
                    });
                })
                ->exists();

            if ($traslapa) {
                return redirect()->back()
                    ->withErrors(['fecha_inicio' => 'La incidencia traslapa con otra incidencia registrada para este elemento.'])
                    ->withInput();
            }

            PersonalIncidencia::create([
                'personal_id' => $personal->id,
                'incidencia_tipo_id' => $tipo->id,
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'] ?? null,
                'hora_inicio' => $validated['hora_inicio'] ?? null,
                'hora_fin' => $validated['hora_fin'] ?? null,
                'folio' => $validated['folio'] ?? null,
                'motivo' => $validated['motivo'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'documento_id' => $validated['documento_id'] ?? null,
                'activo' => 1,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Incidencia registrada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear incidencia: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al registrar la incidencia. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalIncidencia $incidencia)
    {
        if ((int)$incidencia->personal_id !== (int)$personal->id) {
            abort(404);
        }

        $validated = $request->validate([
            'tipo' => 'required|string|max:60',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'folio' => 'nullable|string|max:60',
            'motivo' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000',
            'documento_id' => 'nullable|integer',
        ]);

        try {
            $tipo = $this->resolverTipoIncidencia($validated['tipo']);

            if (!$tipo) {
                return redirect()->back()
                    ->withErrors(['tipo' => 'Tipo de incidencia no válido.'])
                    ->withInput();
            }

            $inicio = $validated['fecha_inicio'];
            $fin = $validated['fecha_fin'] ?? null;

            $traslapa = PersonalIncidencia::query()
                ->where('personal_id', $personal->id)
                ->where('id', '!=', $incidencia->id)
                ->where(function ($q) use ($inicio, $fin) {
                    $q->where(function ($qq) use ($inicio, $fin) {
                        $qq->whereNull('fecha_fin')
                           ->where('fecha_inicio', '<=', $fin ?? $inicio);
                    })->orWhere(function ($qq) use ($inicio, $fin) {
                        $qq->whereNotNull('fecha_fin')
                           ->where('fecha_inicio', '<=', $fin ?? $inicio)
                           ->where('fecha_fin', '>=', $inicio);
                    });
                })
                ->exists();

            if ($traslapa) {
                return redirect()->back()
                    ->withErrors(['fecha_inicio' => 'La incidencia traslapa con otra incidencia registrada para este elemento.'])
                    ->withInput();
            }

            $incidencia->update([
                'incidencia_tipo_id' => $tipo->id,
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'] ?? null,
                'hora_inicio' => $validated['hora_inicio'] ?? null,
                'hora_fin' => $validated['hora_fin'] ?? null,
                'folio' => $validated['folio'] ?? null,
                'motivo' => $validated['motivo'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'documento_id' => $validated['documento_id'] ?? null,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Incidencia actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar incidencia: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al actualizar la incidencia. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalIncidencia $incidencia)
    {
        try {
            if ((int)$incidencia->personal_id !== (int)$personal->id) {
                abort(404);
            }

            $incidencia->delete();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Incidencia eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar incidencia: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al eliminar la incidencia. Inténtelo nuevamente.');
        }
    }

    private function resolverTipoIncidencia(string $raw): ?IncidenciaTipo
    {
        $clave = strtoupper(trim($raw));

        $tipo = IncidenciaTipo::query()
            ->where('activo', 1)
            ->where(function ($query) use ($clave) {
                $query->whereRaw('UPPER(TRIM(clave)) = ?', [$clave])
                    ->orWhereRaw('UPPER(TRIM(nombre)) = ?', [$clave]);
            })
            ->first();

        if (!$tipo && array_key_exists($clave, self::TIPOS_INCIDENCIA_FALLBACK)) {
            $tipo = IncidenciaTipo::query()->find(self::TIPOS_INCIDENCIA_FALLBACK[$clave]);
        }

        return $tipo;
    }
}
