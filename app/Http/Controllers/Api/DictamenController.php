<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dictamen;
use App\Models\Unidad;
use App\Services\Documentos\DocumentoArchivoStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DictamenController extends Controller
{
    private const ANIO_MINIMO = 2017;

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

    public function archivo(Dictamen $dictamen)
    {
        abort_unless($dictamen->archivo_dictamen, 404);

        $nombre = 'dictamen_' . $dictamen->numero_dictamen . '_' . $dictamen->anio . '.pdf';

        return $this->documentos()->response($dictamen->archivo_dictamen, $nombre);
    }

    public function store(Request $request)
    {
        $usuario = $request->user();

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return response()->json([
                'message' => 'Seguridad Vial tiene acceso de solo lectura a dictámenes.',
            ], 403);
        }

        $area = $this->resolverAreaUsuario($usuario);
        $anio = $request->filled('anio') ? (int) $request->input('anio') : now()->year;
        $numeroDictamen = $anio === now()->year
            ? $this->siguienteNumeroPorAnio($anio)
            : ($request->filled('numero_dictamen') ? (int) $request->input('numero_dictamen') : null);

        $request->merge([
            'numero_dictamen' => $numeroDictamen,
            'anio' => $anio,
            'nombre_policia' => strtoupper((string) $request->input('nombre_policia')),
            'nombre_mp'      => strtoupper((string) $request->input('nombre_mp')),
        ]);

        $request->validate([
            'numero_dictamen' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('dictamens', 'numero_dictamen')->where(function ($query) use ($anio, $area) {
                    return $query->where('anio', $anio)->where('area', $area);
                }),
            ],
            'anio'             => 'required|integer|between:' . self::ANIO_MINIMO . ',' . now()->year,
            'nombre_policia'   => 'required|string|max:100',
            'nombre_mp'        => 'nullable|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:10240',
        ], [
            'numero_dictamen.unique' => 'Ya existe ese número de dictamen para el año y área seleccionados.',
            'numero_dictamen.required' => 'Capture el número que aparece en el dictamen histórico.',
            'anio.between' => 'El año debe estar entre ' . self::ANIO_MINIMO . ' y ' . now()->year . '.',
        ]);

        $archivoDictamen = null;
        if ($request->hasFile('archivo_dictamen')) {
            $archivoDictamen = $this->documentos()->putUploadedFile($request->file('archivo_dictamen'), 'dictamenes');
        }

        $dictamen = Dictamen::create([
            'numero_dictamen'  => $numeroDictamen,
            'anio'             => $anio,
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

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return response()->json([
                'message' => 'Seguridad Vial tiene acceso de solo lectura a dictámenes.',
            ], 403);
        }

        if (!$this->puedeEditar($usuario, $dictamen)) {
            return response()->json([
                'message' => 'No tienes permiso para modificar este dictamen.',
            ], 403);
        }

        $request->merge([
            'nombre_policia' => strtoupper((string) $request->input('nombre_policia')),
            'nombre_mp'      => strtoupper((string) $request->input('nombre_mp')),
        ]);

        $area = $this->resolverAreaUsuario($usuario);

        $request->validate([
            'numero_dictamen' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('dictamens', 'numero_dictamen')
                    ->ignore($dictamen->id)
                    ->where(function ($query) use ($request, $area) {
                        return $query
                            ->where('anio', (int) $request->input('anio'))
                            ->where('area', $area);
                    }),
            ],
            'anio' => 'required|integer|between:' . self::ANIO_MINIMO . ',' . now()->year,
            'nombre_policia' => 'required|string|max:100',
            'nombre_mp' => 'nullable|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:10240',
        ], [
            'numero_dictamen.unique' => 'Ya existe ese número de dictamen para el año y área seleccionados.',
            'anio.between' => 'El año debe estar entre ' . self::ANIO_MINIMO . ' y ' . now()->year . '.',
        ]);

        $archivoDictamen = $dictamen->archivo_dictamen;

        if ($request->hasFile('archivo_dictamen')) {

            $archivoAnterior = $archivoDictamen;
            $archivoDictamen = $this->documentos()->putUploadedFile($request->file('archivo_dictamen'), 'dictamenes');

            if ($archivoAnterior && $archivoAnterior !== $archivoDictamen) {
                $this->documentos()->delete($archivoAnterior);
            }
        }

        $dictamen->update([
            'numero_dictamen'  => (int) $request->input('numero_dictamen'),
            'anio'             => (int) $request->input('anio'),
            'nombre_policia'   => $request->input('nombre_policia'),
            'nombre_mp'        => $request->input('nombre_mp'),
            'area'             => $area,
            'archivo_dictamen' => $archivoDictamen,
            'updated_by'       => $usuario->id,
        ]);

        return response()->json([
            'message' => 'Dictamen actualizado exitosamente.',
            'nombre_usuario' => $usuario->name,
            'area_autollenada' => $area,
            'data' => $dictamen->fresh(),
        ]);
    }

    public function destroy(Request $request, Dictamen $dictamen)
    {
        $usuario = $request->user();

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return response()->json([
                'message' => 'Seguridad Vial tiene acceso de solo lectura a dictámenes.',
            ], 403);
        }

        if (!$usuario->hasRole('Administrador')) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar este dictamen.',
            ], 403);
        }

        $archivo = $dictamen->archivo_dictamen;

        $dictamen->delete();

        if ($archivo) {
            $this->documentos()->delete($archivo);
        }

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
        return ((int) Dictamen::query()->where('anio', $anio)->max('numero_dictamen')) + 1;
    }

    private function resolverAreaUsuario($usuario): string
    {
        $unidadId = !empty($usuario->unidad_id) ? (int) $usuario->unidad_id : 1;

        $unidadNombre = Unidad::where('id', $unidadId)->value('nombre');

        return $unidadNombre ? strtoupper((string) $unidadNombre) : 'SINIESTROS';
    }

    private function puedeEditar($usuario, Dictamen $dictamen): bool
    {
        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return false;
        }

        return ((int) $usuario->id === (int) $dictamen->created_by) || $usuario->hasRole('Administrador');
    }

    private function esSeguridadVialSoloLectura($usuario): bool
    {
        return $usuario
            && (int) ($usuario->unidad_id ?? 0) === 3
            && !$usuario->hasRole('Superadmin');
    }

    private function documentos(): DocumentoArchivoStorage
    {
        return app(DocumentoArchivoStorage::class);
    }
}
