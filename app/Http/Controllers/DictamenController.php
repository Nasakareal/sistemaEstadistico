<?php

namespace App\Http\Controllers;

use App\Models\Dictamen;
use App\Models\Unidad;
use App\Services\Documentos\DocumentoArchivoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DictamenController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            $usuario = Auth::user();

            if (
                !$usuario ||
                (
                    !$usuario->hasRole('Superadmin')
                    && !in_array((int) $usuario->unidad_id, [1, 3], true)
                )
            ) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $anioActual = now()->year;
        $anioSeleccionado = (int) $request->get('anio', $anioActual);

        $mesDesde = (int) $request->get('mes_desde', 1);
        $mesHasta = (int) $request->get('mes_hasta', 12);

        if ($mesDesde < 1 || $mesDesde > 12) {
            $mesDesde = 1;
        }

        if ($mesHasta < 1 || $mesHasta > 12) {
            $mesHasta = 12;
        }

        if ($mesDesde > $mesHasta) {
            [$mesDesde, $mesHasta] = [$mesHasta, $mesDesde];
        }

        $query = Dictamen::query()
            ->where('anio', $anioSeleccionado)
            ->whereMonth('created_at', '>=', $mesDesde)
            ->whereMonth('created_at', '<=', $mesHasta);

        $dictamenes = $query
            ->orderByDesc('numero_dictamen')
            ->get();

        $totalDictamenes = (clone $query)->count();

        $anios = Dictamen::query()
            ->select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        return view('dictamenes.index', compact(
            'dictamenes',
            'anios',
            'anioActual',
            'anioSeleccionado',
            'mesDesde',
            'mesHasta',
            'meses',
            'totalDictamenes'
        ));
    }

    public function create()
    {
        $anioActual = now()->year;
        $usuario = auth()->user();

        $unidadNombre = null;
        if ($usuario->unidad_id) {
            $unidadNombre = Unidad::where('id', $usuario->unidad_id)->value('nombre');
        }

        $ultimoDictamen = Dictamen::query()
            ->where('anio', $anioActual)
            ->orderBy('numero_dictamen', 'desc')
            ->first();

        $numeroSiguiente = $ultimoDictamen ? ($ultimoDictamen->numero_dictamen + 1) : 1;

        return view('dictamenes.create', compact('numeroSiguiente', 'unidadNombre'));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return redirect()->route('dictamenes.index')
                ->with('error', 'Seguridad Vial tiene acceso de solo lectura a dictámenes.');
        }

        $unidadNombre = null;
        if ($usuario->unidad_id) {
            $unidadNombre = Unidad::where('id', $usuario->unidad_id)->value('nombre');
        }
        if (!$unidadNombre) {
            $unidadNombre = 'SIN ASIGNAR';
        }

        $request->merge([
            'nombre_policia' => strtoupper((string) $request->input('nombre_policia')),
            'nombre_mp'      => $request->filled('nombre_mp') ? strtoupper((string) $request->input('nombre_mp')) : null,
        ]);

        $request->validate([
            'nombre_policia'   => 'required|string|max:100',
            'nombre_mp'        => 'nullable|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $archivoDictamen = null;
        if ($request->hasFile('archivo_dictamen')) {
            $archivoDictamen = $this->documentos()->putUploadedFile($request->file('archivo_dictamen'), 'dictamenes');
        }

        $anioActual = now()->year;

        $ultimoDictamen = Dictamen::query()
            ->where('anio', $anioActual)
            ->orderBy('numero_dictamen', 'desc')
            ->first();

        $numeroSiguiente = $ultimoDictamen ? ($ultimoDictamen->numero_dictamen + 1) : 1;

        Dictamen::create([
            'numero_dictamen'  => $numeroSiguiente,
            'anio'             => $anioActual,
            'nombre_policia'   => $request->input('nombre_policia'),
            'nombre_mp'        => $request->input('nombre_mp'),
            'area'             => $unidadNombre,
            'archivo_dictamen' => $archivoDictamen,
            'created_by'       => $usuario->id,
        ]);

        return redirect()->route('dictamenes.index')->with('success', 'Dictamen creado exitosamente.');
    }

    public function edit(Dictamen $dictamen)
    {
        $usuario = auth()->user();

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return redirect()->route('dictamenes.index')
                ->with('error', 'Seguridad Vial tiene acceso de solo lectura a dictámenes.');
        }

        if ($usuario->hasRole('Administrador')) {
            return view('dictamenes.edit', compact('dictamen'));
        }

        if ($usuario->hasRole('Perito')) {
            if ($usuario->id !== $dictamen->created_by) {
                return redirect()->route('dictamenes.index')
                    ->with('error', 'No tienes permiso para editar este dictamen.');
            }

            if (!$usuario->can('editar dictamenes')) {
                return redirect()->route('dictamenes.index')
                    ->with('error', 'No tienes permiso para editar dictámenes.');
            }

            return view('dictamenes.edit', compact('dictamen'));
        }

        if ($usuario->can('editar dictamenes')) {
            return view('dictamenes.edit', compact('dictamen'));
        }

        return redirect()->route('dictamenes.index')
            ->with('error', 'No tienes permiso para editar dictámenes.');
    }

    public function update(Request $request, Dictamen $dictamen)
    {
        $usuario = auth()->user();

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return redirect()->route('dictamenes.index')
                ->with('error', 'Seguridad Vial tiene acceso de solo lectura a dictámenes.');
        }

        if ($usuario->hasRole('Administrador')) {
        } elseif ($usuario->hasRole('Perito')) {

            if ($usuario->id !== $dictamen->created_by || !$usuario->can('editar dictamenes')) {
                return redirect()->route('dictamenes.index')
                    ->with('error', 'No tienes permiso para modificar este dictamen.');
            }

        } elseif (!$usuario->can('editar dictamenes')) {

            return redirect()->route('dictamenes.index')
                ->with('error', 'No tienes permiso para modificar este dictamen.');
        }

        $request->merge([
            'nombre_policia' => strtoupper((string) $request->input('nombre_policia')),
            'nombre_mp'      => $request->filled('nombre_mp') ? strtoupper((string) $request->input('nombre_mp')) : null,
            'area'           => strtoupper((string) $request->input('area')),
        ]);

        $request->validate([
            'numero_dictamen'  => 'required|integer|unique:dictamens,numero_dictamen,' . $dictamen->id,
            'anio'             => 'required|digits:4',
            'nombre_policia'   => 'required|string|max:100',
            'nombre_mp'        => 'nullable|string|max:100',
            'area'             => 'required|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:10240',
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
            'numero_dictamen'  => $request->input('numero_dictamen'),
            'anio'             => $request->input('anio'),
            'nombre_policia'   => $request->input('nombre_policia'),
            'nombre_mp'        => $request->input('nombre_mp'),
            'area'             => $request->input('area'),
            'archivo_dictamen' => $archivoDictamen,
            'updated_by'       => $usuario->id,
        ]);

        return redirect()->route('dictamenes.index')
            ->with('success', 'Dictamen actualizado exitosamente.');
    }

    public function show(Dictamen $dictamen)
    {
        return view('dictamenes.show', compact('dictamen'));
    }

    public function archivo(Dictamen $dictamen)
    {
        abort_unless($dictamen->archivo_dictamen, 404);

        $nombre = 'dictamen_' . $dictamen->numero_dictamen . '_' . $dictamen->anio . '.pdf';

        return $this->documentos()->response($dictamen->archivo_dictamen, $nombre);
    }

    public function destroy(Dictamen $dictamen)
    {
        $usuario = auth()->user();

        if ($this->esSeguridadVialSoloLectura($usuario)) {
            return redirect()->route('dictamenes.index')
                ->with('error', 'Seguridad Vial tiene acceso de solo lectura a dictámenes.');
        }

        if (!$usuario->hasRole('Administrador') && !$usuario->hasRole('Superadmin')) {
            return redirect()->route('dictamenes.index')
                ->with('error', 'No tienes permiso para eliminar este dictamen.');
        }

        $archivo = $dictamen->archivo_dictamen;
        $dictamen->delete();

        if ($archivo) {
            $this->documentos()->delete($archivo);
        }

        return redirect()->route('dictamenes.index')
            ->with('success', 'Dictamen eliminado exitosamente.');
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
