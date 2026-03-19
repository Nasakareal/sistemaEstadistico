<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DelegacionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activa = $request->query('activa', null);

        $delegaciones = Delegacion::query()
            ->with(['padre', 'hijas'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('municipio', 'like', "%{$q}%")
                      ->orWhere('clave', 'like', "%{$q}%");
                });
            })
            ->when($activa !== null && $activa !== '', function ($query) use ($activa) {
                $query->where('activa', (int) $activa ? 1 : 0);
            })
            ->orderBy('nombre')
            ->get();

        return view('admin.settings.delegaciones.index', compact('delegaciones', 'q', 'activa'));
    }

    public function create()
    {
        $delegacionesPadre = Delegacion::query()
            ->whereNull('delegacion_padre_id')
            ->orderBy('nombre')
            ->get();

        return view('admin.settings.delegaciones.create', compact('delegacionesPadre'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'clave' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'activa' => ['nullable', 'boolean'],

            'delegacion_padre_id' => ['nullable', 'integer', 'exists:delegaciones,id'],

            'hijas' => ['nullable', 'array'],
            'hijas.*.clave' => ['nullable', 'string', 'max:50'],
            'hijas.*.nombre' => ['required_with:hijas', 'nullable', 'string', 'max:255'],
            'hijas.*.municipio' => ['nullable', 'string', 'max:255'],
            'hijas.*.activa' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $delegacion = Delegacion::create([
                'clave' => $data['clave'] ?? null,
                'nombre' => $data['nombre'],
                'municipio' => $data['municipio'] ?? null,
                'activa' => (bool) ($data['activa'] ?? true),
                'delegacion_padre_id' => $data['delegacion_padre_id'] ?? null,
            ]);

            $hijas = $data['hijas'] ?? [];
            foreach ($hijas as $h) {
                $nombreHija = trim((string) ($h['nombre'] ?? ''));
                if ($nombreHija === '') continue;

                Delegacion::create([
                    'clave' => $h['clave'] ?? null,
                    'nombre' => $nombreHija,
                    'municipio' => $h['municipio'] ?? null,
                    'activa' => (bool) ($h['activa'] ?? true),
                    'delegacion_padre_id' => $delegacion->id,
                ]);
            }
        });

        return redirect()->route('delegaciones.index')->with('success', 'Delegación creada correctamente.');
    }

    public function show(Delegacion $delegacion)
    {
        $delegacion->load(['padre', 'hijas']);

        return view('admin.settings.delegaciones.show', compact('delegacion'));
    }

    public function edit(Delegacion $delegacion)
    {
        $delegacion->load(['hijas', 'padre']);

        $delegacionesPadre = Delegacion::query()
            ->whereNull('delegacion_padre_id')
            ->where('id', '!=', $delegacion->id)
            ->orderBy('nombre')
            ->get();

        return view('admin.settings.delegaciones.edit', compact('delegacion', 'delegacionesPadre'));
    }

    public function update(Request $request, Delegacion $delegacion)
    {
        $data = $request->validate([
            'clave' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'activa' => ['nullable', 'boolean'],

            'delegacion_padre_id' => [
                'nullable',
                'integer',
                'exists:delegaciones,id',
                Rule::notIn([$delegacion->id]),
            ],

            'hijas' => ['nullable', 'array'],
            'hijas.*.id' => ['nullable', 'integer', 'exists:delegaciones,id'],
            'hijas.*.clave' => ['nullable', 'string', 'max:50'],
            'hijas.*.nombre' => ['required_with:hijas', 'nullable', 'string', 'max:255'],
            'hijas.*.municipio' => ['nullable', 'string', 'max:255'],
            'hijas.*.activa' => ['nullable', 'boolean'],

            'hijas_delete' => ['nullable', 'array'],
            'hijas_delete.*' => ['integer', 'exists:delegaciones,id'],
        ]);

        DB::transaction(function () use ($data, $delegacion) {

            if (!empty($data['delegacion_padre_id'])) {
                $isDirectChild = Delegacion::query()
                    ->where('delegacion_padre_id', $delegacion->id)
                    ->where('id', (int) $data['delegacion_padre_id'])
                    ->exists();

                if ($isDirectChild) {
                    abort(422, 'No puedes asignar como padre a una hija directa.');
                }
            }

            $delegacion->update([
                'clave' => $data['clave'] ?? null,
                'nombre' => $data['nombre'],
                'municipio' => $data['municipio'] ?? null,
                'activa' => (bool) ($data['activa'] ?? true),
                'delegacion_padre_id' => $data['delegacion_padre_id'] ?? null,
            ]);

            $toDelete = $data['hijas_delete'] ?? [];
            if (!empty($toDelete)) {
                Delegacion::query()
                    ->where('delegacion_padre_id', $delegacion->id)
                    ->whereIn('id', $toDelete)
                    ->delete();
            }

            $hijas = $data['hijas'] ?? [];
            foreach ($hijas as $h) {
                $nombreHija = trim((string) ($h['nombre'] ?? ''));
                if ($nombreHija === '') continue;

                $hijaId = isset($h['id']) ? (int) $h['id'] : null;

                if ($hijaId) {
                    $hija = Delegacion::query()
                        ->where('id', $hijaId)
                        ->where('delegacion_padre_id', $delegacion->id)
                        ->first();

                    if ($hija) {
                        $hija->update([
                            'clave' => $h['clave'] ?? null,
                            'nombre' => $nombreHija,
                            'municipio' => $h['municipio'] ?? null,
                            'activa' => (bool) ($h['activa'] ?? true),
                        ]);
                    }
                } else {
                    Delegacion::create([
                        'clave' => $h['clave'] ?? null,
                        'nombre' => $nombreHija,
                        'municipio' => $h['municipio'] ?? null,
                        'activa' => (bool) ($h['activa'] ?? true),
                        'delegacion_padre_id' => $delegacion->id,
                    ]);
                }
            }
        });

        return redirect()->route('delegaciones.edit', $delegacion)->with('success', 'Delegación actualizada correctamente.');
    }

    public function destroy(Delegacion $delegacion)
    {
        DB::transaction(function () use ($delegacion) {

            Delegacion::query()
                ->where('delegacion_padre_id', $delegacion->id)
                ->delete();

            DB::table('delegacion_user')
                ->where('delegacion_id', $delegacion->id)
                ->delete();

            DB::table('users')
                ->where('delegacion_id', $delegacion->id)
                ->update(['delegacion_id' => null]);

            $delegacion->delete();
        });

        return redirect()->route('delegaciones.index')->with('success', 'Delegación eliminada correctamente.');
    }

    public function hijas(Delegacion $delegacion)
    {
        $hijas = $delegacion->hijas()
            ->orderBy('nombre')
            ->get(['id', 'delegacion_padre_id', 'clave', 'nombre', 'municipio', 'activa']);

        return response()->json(['ok' => true, 'data' => $hijas]);
    }
}
