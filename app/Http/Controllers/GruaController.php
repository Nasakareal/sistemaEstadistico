<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grua;
use App\Models\Delegacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GruaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            $usuario = Auth::user();

            if (
                !$usuario ||
                (!$usuario->hasRole('Superadmin') && (int)$usuario->unidad_id !== 1)
            ) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index()
    {
        $gruas = Grua::with(['unidades', 'delegaciones'])
            ->orderBy('nombre')
            ->get();

        return view('gruas.index', compact('gruas'));
    }

    public function create()
    {
        $delegaciones = Delegacion::where('activa', 1)
            ->where('nombre', '!=', 'Morelia')
            ->orderBy('nombre')
            ->get();

        return view('gruas.create', compact('delegaciones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'ubicacion_corralon' => 'nullable|string',
            'telefono' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'tipo_asignacion' => 'required|in:siniestros,delegaciones',
            'delegaciones' => 'nullable|array',
            'delegaciones.*' => 'integer|exists:delegaciones,id',
        ]);

        $tipoAsignacion = $request->input('tipo_asignacion');

        if ($tipoAsignacion === 'delegaciones' && empty($request->delegaciones)) {
            return back()->withErrors([
                'delegaciones' => 'Selecciona al menos una delegación'
            ])->withInput();
        }

        DB::transaction(function () use ($request, $tipoAsignacion) {
            $data = $request->only([
                'nombre',
                'direccion',
                'ubicacion_corralon',
                'telefono',
                'email',
            ]);

            $data['nombre'] = strtoupper(trim($data['nombre']));
            $data['direccion'] = filled($data['direccion'] ?? null) ? strtoupper(trim($data['direccion'])) : null;
            $data['ubicacion_corralon'] = filled($data['ubicacion_corralon'] ?? null) ? strtoupper(trim($data['ubicacion_corralon'])) : null;
            $data['telefono'] = filled($data['telefono'] ?? null) ? strtoupper(trim($data['telefono'])) : null;
            $data['email'] = filled($data['email'] ?? null) ? strtoupper(trim($data['email'])) : null;

            $grua = Grua::create($data);

            if ($tipoAsignacion === 'siniestros') {
                $grua->unidades()->sync([1]);
                $grua->delegaciones()->detach();
            }

            if ($tipoAsignacion === 'delegaciones') {
                $delegaciones = collect($request->delegaciones ?? [])
                    ->map(function ($id) {
                        return (int)$id;
                    })
                    ->unique()
                    ->values()
                    ->toArray();

                $grua->unidades()->detach(1);
                $grua->delegaciones()->sync($delegaciones);
            }
        });

        return redirect()->route('gruas.index')
            ->with('success', 'Grúa registrada correctamente.');
    }

    public function show($id)
    {
        $grua = Grua::with(['unidades', 'delegaciones'])->findOrFail($id);

        return view('gruas.show', compact('grua'));
    }

    public function edit($id)
    {
        $grua = Grua::with(['unidades', 'delegaciones'])->findOrFail($id);

        $delegaciones = Delegacion::where('activa', 1)
            ->where('nombre', '!=', 'Morelia')
            ->orderBy('nombre')
            ->get();

        return view('gruas.edit', compact('grua', 'delegaciones'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'ubicacion_corralon' => 'nullable|string',
            'telefono' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'tipo_asignacion' => 'required|in:siniestros,delegaciones',
            'delegaciones' => 'nullable|array',
            'delegaciones.*' => 'integer|exists:delegaciones,id',
        ]);

        $tipoAsignacion = $request->input('tipo_asignacion');

        if ($tipoAsignacion === 'delegaciones' && empty($request->delegaciones)) {
            return back()->withErrors([
                'delegaciones' => 'Selecciona al menos una delegación'
            ])->withInput();
        }

        $grua = Grua::findOrFail($id);

        DB::transaction(function () use ($request, $grua, $tipoAsignacion) {
            $data = $request->only([
                'nombre',
                'direccion',
                'ubicacion_corralon',
                'telefono',
                'email',
            ]);

            $data['nombre'] = strtoupper(trim($data['nombre']));
            $data['direccion'] = filled($data['direccion'] ?? null) ? strtoupper(trim($data['direccion'])) : null;
            $data['ubicacion_corralon'] = filled($data['ubicacion_corralon'] ?? null) ? strtoupper(trim($data['ubicacion_corralon'])) : null;
            $data['telefono'] = filled($data['telefono'] ?? null) ? strtoupper(trim($data['telefono'])) : null;
            $data['email'] = filled($data['email'] ?? null) ? strtoupper(trim($data['email'])) : null;

            $grua->update($data);

            if ($tipoAsignacion === 'siniestros') {
                $grua->unidades()->sync([1]);
                $grua->delegaciones()->detach();
            }

            if ($tipoAsignacion === 'delegaciones') {
                $delegaciones = collect($request->delegaciones ?? [])
                    ->map(function ($id) {
                        return (int)$id;
                    })
                    ->unique()
                    ->values()
                    ->toArray();

                $grua->unidades()->detach(1);
                $grua->delegaciones()->sync($delegaciones);
            }
        });

        return redirect()->route('gruas.index')
            ->with('success', 'Grúa actualizada correctamente.');
    }

    public function destroy($id)
    {
        $grua = Grua::findOrFail($id);
        $grua->delete();

        return redirect()->route('gruas.index')
            ->with('success', 'Grúa eliminada correctamente.');
    }
}
