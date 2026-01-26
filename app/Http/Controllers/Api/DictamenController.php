<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dictamen;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DictamenController extends Controller
{
    public function index(Request $request)
    {
        $anioActual = now()->year;
        $anioSeleccionado = (int) $request->query('anio', $anioActual);

        $query = Dictamen::query()->where('anio', $anioSeleccionado);

        $dictamenes = $query->orderByDesc('numero_dictamen')->get();

        $anios = Dictamen::select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return response()->json([
            'anio_actual' => $anioActual,
            'anio_seleccionado' => $anioSeleccionado,
            'anios' => $anios,
            'data' => $dictamenes,
        ]);
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $anio = $request->query('anio');

        $query = Dictamen::query();

        if ($anio) {
            $query->where('anio', (int) $anio);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre_policia', 'like', "%{$q}%")
                    ->orWhere('nombre_mp', 'like', "%{$q}%")
                    ->orWhere('area', 'like', "%{$q}%")
                    ->orWhere('numero_dictamen', 'like', "%{$q}%");
            });
        }

        $dictamenes = $query->orderByDesc('anio')
            ->orderByDesc('numero_dictamen')
            ->limit(50)
            ->get();

        return response()->json([
            'q' => $q,
            'anio' => $anio ? (int) $anio : null,
            'data' => $dictamenes,
        ]);
    }

    public function show(Dictamen $dictamen)
    {
        return response()->json([
            'data' => $dictamen,
        ]);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        $request->merge([
            'nombre_policia' => strtoupper((string) $request->input('nombre_policia')),
            'nombre_mp'      => strtoupper((string) $request->input('nombre_mp')),
        ]);

        $request->validate([
            'nombre_policia'   => 'required|string|max:100',
            'nombre_mp'        => 'required|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $area = $this->resolverAreaUsuario($usuario);

        $archivoDictamen = null;
        if ($request->hasFile('archivo_dictamen')) {
            $archivoDictamen = $request->file('archivo_dictamen')->store('dictamenes', 'public');
        }

        $anioActual = now()->year;

        $numeroSiguiente = $this->siguienteNumeroPorAnio($anioActual);

        $dictamen = Dictamen::create([
            'numero_dictamen'  => $numeroSiguiente,
            'anio'             => $anioActual,
            'nombre_policia'   => $request->input('nombre_policia'),
            'nombre_mp'        => $request->input('nombre_mp'),
            'area'             => $area,
            'archivo_dictamen' => $archivoDictamen,
            'created_by'       => $usuario->id,
        ]);

        return response()->json([
            'message' => 'Dictamen creado exitosamente.',
            'nombre_usuario' => $usuario->name,
            'area_autollenada' => $area,
            'data' => $dictamen,
        ], 201);
    }

    public function update(Request $request, Dictamen $dictamen)
    {
        $usuario = $request->user();

        if (!$this->puedeEditar($usuario, $dictamen)) {
            return response()->json([
                'message' => 'No tienes permiso para modificar este dictamen.',
            ], 403);
        }

        $request->merge([
            'nombre_policia' => strtoupper((string) $request->input('nombre_policia')),
            'nombre_mp'      => strtoupper((string) $request->input('nombre_mp')),
            'area'           => strtoupper((string) $request->input('area')),
        ]);

        $request->validate([
            'numero_dictamen' => [
                'required',
                'integer',
                Rule::unique('dictamens', 'numero_dictamen')->ignore($dictamen->id),
            ],
            'anio' => 'required|digits:4',
            'nombre_policia' => 'required|string|max:100',
            'nombre_mp' => 'required|string|max:100',
            'area' => 'required|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $archivoDictamen = $dictamen->archivo_dictamen;

        if ($request->hasFile('archivo_dictamen')) {

            if ($archivoDictamen && Storage::disk('public')->exists($archivoDictamen)) {
                Storage::disk('public')->delete($archivoDictamen);
            }

            $archivoDictamen = $request->file('archivo_dictamen')->store('dictamenes', 'public');
        }

        $dictamen->update([
            'numero_dictamen'  => (int) $request->input('numero_dictamen'),
            'anio'             => (int) $request->input('anio'),
            'nombre_policia'   => $request->input('nombre_policia'),
            'nombre_mp'        => $request->input('nombre_mp'),
            'area'             => $request->input('area'),
            'archivo_dictamen' => $archivoDictamen,
            'updated_by'       => $usuario->id,
        ]);

        return response()->json([
            'message' => 'Dictamen actualizado exitosamente.',
            'nombre_usuario' => $usuario->name,
            'data' => $dictamen->fresh(),
        ]);
    }

    public function destroy(Request $request, Dictamen $dictamen)
    {
        $usuario = $request->user();

        if (!$usuario->hasRole('Administrador')) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar este dictamen.',
            ], 403);
        }

        if ($dictamen->archivo_dictamen && Storage::disk('public')->exists($dictamen->archivo_dictamen)) {
            Storage::disk('public')->delete($dictamen->archivo_dictamen);
        }

        $dictamen->delete();

        return response()->json([
            'message' => 'Dictamen eliminado exitosamente.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers (privados)
    |--------------------------------------------------------------------------
    */

    private function siguienteNumeroPorAnio(int $anio): int
    {
        $ultimo = Dictamen::where('anio', $anio)
            ->orderBy('numero_dictamen', 'desc')
            ->first();

        return $ultimo ? ((int) $ultimo->numero_dictamen + 1) : 1;
    }

    private function resolverAreaUsuario($usuario): string
    {
        if (!empty($usuario->area)) {
            return strtoupper((string) $usuario->area);
        }

        if (!empty($usuario->unidad_id)) {
            $unidadNombre = Unidad::where('id', $usuario->unidad_id)->value('nombre');
            if ($unidadNombre) {
                return strtoupper((string) $unidadNombre);
            }
        }

        return 'SIN ASIGNAR';
    }

    private function puedeEditar($usuario, Dictamen $dictamen): bool
    {
        return ((int) $usuario->id === (int) $dictamen->created_by) || $usuario->hasRole('Administrador');
    }
}
