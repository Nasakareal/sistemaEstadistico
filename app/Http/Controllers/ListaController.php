<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lista;
use Illuminate\Http\Request;

class ListaController extends Controller
{
    public function index(Request $request)
    {
        $areaSeleccionada = $request->get('area', null);

        // Filtrar listas por área si se selecciona una
        $listas = Lista::when($areaSeleccionada, function ($query) use ($areaSeleccionada) {
            $query->where('area', $areaSeleccionada);
        })->get();

        // Obtener áreas disponibles
        $areas = User::select('area')->distinct()->pluck('area');

        return view('listas.index', compact('listas', 'areas', 'areaSeleccionada'));
    }

    public function create()
    {
        $usuario = auth()->user();

        $rolUsuario = $usuario->roles->first()->name ?? 'Usuario';
        $areaUsuario = $rolUsuario === 'Administrador' ? null : $usuario->area;

        // Áreas disponibles para el administrador
        $areasDisponibles = User::select('area')->distinct()->pluck('area');

        return view('listas.create', compact('rolUsuario', 'areaUsuario', 'areasDisponibles'));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();
        $rolUsuario = $usuario->roles->first()->name ?? 'Usuario';
        $areaUsuario = $rolUsuario === 'Administrador' ? $request->input('area') : $usuario->area;

        $request->validate([
            'area' => 'required|string|max:255',
            'total_asistentes' => 'nullable|integer',
            'foto_1' => 'nullable|image|max:2048',
            'foto_2' => 'nullable|image|max:2048',
            'observaciones' => 'nullable|string',
        ]);

        // Manejo de archivos (fotos)
        $foto1 = $request->hasFile('foto_1') ? $request->file('foto_1')->store('listas/fotos', 'public') : null;
        $foto2 = $request->hasFile('foto_2') ? $request->file('foto_2')->store('listas/fotos', 'public') : null;

        Lista::create([
            'area' => $areaUsuario,
            'total_asistentes' => $request->input('total_asistentes'),
            'foto_1' => $foto1,
            'foto_2' => $foto2,
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->route('listas.index')
                         ->with('success', 'Pase de lista registrado exitosamente.');
    }

    public function edit(Lista $lista)
    {
        $usuario = auth()->user();
        $rolUsuario = $usuario->roles->first()->name ?? 'Usuario';

        $areasDisponibles = User::select('area')->distinct()->pluck('area');

        return view('listas.edit', compact('lista', 'rolUsuario', 'areasDisponibles'));
    }

    public function update(Request $request, Lista $lista)
    {
        $request->validate([
            'area' => 'required|string|max:255',
            'total_asistentes' => 'nullable|integer',
            'foto_1' => 'nullable|image|max:2048',
            'foto_2' => 'nullable|image|max:2048',
            'observaciones' => 'nullable|string',
        ]);

        // Manejo de archivos (fotos)
        if ($request->hasFile('foto_1')) {
            $lista->foto_1 = $request->file('foto_1')->store('listas/fotos', 'public');
        }

        if ($request->hasFile('foto_2')) {
            $lista->foto_2 = $request->file('foto_2')->store('listas/fotos', 'public');
        }

        $lista->update([
            'area' => $request->input('area'),
            'total_asistentes' => $request->input('total_asistentes'),
            'observaciones' => $request->input('observaciones'),
        ]);

        return redirect()->route('listas.index')
                         ->with('success', 'Pase de lista actualizado exitosamente.');
    }

    public function show(Lista $lista)
    {
        return view('listas.show', compact('lista'));
    }

    public function destroy(Lista $lista)
    {
        $lista->delete();

        return redirect()->route('listas.index')
                         ->with('success', 'Pase de lista eliminado exitosamente.');
    }
}
