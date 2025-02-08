<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Dictamen;
use Illuminate\Http\Request;

class DictamenController extends Controller
{
    public function index(Request $request)
    {
        $anioActual = now()->year;
        $anioSeleccionado = $request->get('anio', $anioActual);
        $dictamenes = Dictamen::where('anio', $anioSeleccionado)->get();

        $anios = Dictamen::select('anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');

        return view('dictamenes.index', compact('dictamenes', 'anios', 'anioActual'));
    }

    public function create()
    {
        $anioActual = now()->year;
        $usuario = auth()->user();

        $rolUsuario = $usuario->roles->first()->name ?? 'Usuario';
        $areaUsuario = $rolUsuario === 'Administrador' ? null : $usuario->area;

        $areasDisponibles = User::select('area')->distinct()->pluck('area');

        $ultimoDictamen = Dictamen::where('anio', $anioActual)
                                  ->orderBy('numero_dictamen', 'desc')
                                  ->first();

        $numeroSiguiente = $ultimoDictamen ? $ultimoDictamen->numero_dictamen + 1 : 1;

        return view('dictamenes.create', compact('numeroSiguiente', 'rolUsuario', 'areaUsuario', 'areasDisponibles'));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();
        $rolUsuario = $usuario->roles->first()->name ?? 'Usuario';
        $areaUsuario = $rolUsuario === 'Administrador' ? $request->input('area') : $usuario->area;
        $anioActual = $request->input('anio');

        $request->merge([
            'nombre_policia' => strtoupper($request->input('nombre_policia')),
            'nombre_mp' => strtoupper($request->input('nombre_mp')),
            'area' => strtoupper($request->input('area')),
        ]);

        $request->validate([
            'nombre_policia' => 'required|string|max:100',
            'nombre_mp' => 'required|string|max:100',
            'area' => 'required|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $archivoDictamen = null;
        if ($request->hasFile('archivo_dictamen')) {
            $archivoDictamen = $request->file('archivo_dictamen')->store('dictamenes', 'public');
        }

        $ultimoDictamen = Dictamen::where('anio', $anioActual)
                                  ->orderBy('numero_dictamen', 'desc')
                                  ->first();

        $numeroSiguiente = $ultimoDictamen ? $ultimoDictamen->numero_dictamen + 1 : 1;

        Dictamen::create([
            'numero_dictamen' => $numeroSiguiente,
            'anio' => $anioActual,
            'nombre_policia' => $request->input('nombre_policia'),
            'nombre_mp' => $request->input('nombre_mp'),
            'area' => $areaUsuario,
            'archivo_dictamen' => $archivoDictamen,
            'created_by' => $usuario->id,
        ]);

        return redirect()->route('dictamenes.index')
                         ->with('success', 'Dictamen creado exitosamente.');
    }

    public function edit(Dictamen $dictamen)
    {
        $usuario = auth()->user();

        if ($usuario->id !== $dictamen->created_by && !$usuario->hasRole('Administrador')) {
            return redirect()->route('dictamenes.index')
                             ->with('error', 'No tienes permiso para editar este dictamen.');
        }

        return view('dictamenes.edit', compact('dictamen'));
    }

    public function update(Request $request, Dictamen $dictamen)
    {
        $usuario = auth()->user();

        if ($usuario->id !== $dictamen->created_by && !$usuario->hasRole('Administrador')) {
            return redirect()->route('dictamenes.index')
                             ->with('error', 'No tienes permiso para modificar este dictamen.');
        }

        $request->merge([
            'nombre_policia' => strtoupper($request->input('nombre_policia')),
            'nombre_mp' => strtoupper($request->input('nombre_mp')),
            'area' => strtoupper($request->input('area')),
        ]);

        $request->validate([
            'numero_dictamen' => 'required|integer|unique:dictamens,numero_dictamen,' . $dictamen->id,
            'anio' => 'required|digits:4',
            'nombre_policia' => 'required|string|max:100',
            'nombre_mp' => 'required|string|max:100',
            'area' => 'required|string|max:100',
            'archivo_dictamen' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $archivoDictamen = $dictamen->archivo_dictamen;
        if ($request->hasFile('archivo_dictamen')) {
            $archivoDictamen = $request->file('archivo_dictamen')->store('dictamenes', 'public');
        }

        $dictamen->update([
            'numero_dictamen' => $request->input('numero_dictamen'),
            'anio' => $request->input('anio'),
            'nombre_policia' => $request->input('nombre_policia'),
            'nombre_mp' => $request->input('nombre_mp'),
            'area' => $request->input('area'),
            'archivo_dictamen' => $archivoDictamen,
            'updated_by' => $usuario->id,
        ]);

        return redirect()->route('dictamenes.index')
                         ->with('success', 'Dictamen actualizado exitosamente.');
    }

    public function show(Dictamen $dictamen)
    {
        return view('dictamenes.show', compact('dictamen'));
    }

    public function destroy(Dictamen $dictamen)
    {
        $usuario = auth()->user();

        if (!$usuario->hasRole('Administrador')) {
            return redirect()->route('dictamenes.index')
                             ->with('error', 'No tienes permiso para eliminar este dictamen.');
        }

        $dictamen->delete();

        return redirect()->route('dictamenes.index')
                         ->with('success', 'Dictamen eliminado exitosamente.');
    }
}
