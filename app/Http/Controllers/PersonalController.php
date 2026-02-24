<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Unidad;
use App\Models\Turno;
use App\Models\Patrulla;
use App\Models\Armamento;
use App\Models\PersonalAsignacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonalController extends Controller
{
    public function index()
    {
        $personals = Personal::query()
            ->with(['unidad', 'turno', 'patrulla', 'user'])
            ->orderByDesc('estatus')
            ->orderBy('nombre')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->get();

        return view('admin.settings.personal.index', compact('personals'));
    }

    public function create()
    {
        $unidades = Unidad::query()
            ->where('activa', 1)
            ->orderBy('nombre')
            ->get();

        $turnos = Turno::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $patrullas = Patrulla::query()
            ->where('activa', 1)
            ->orderBy('numero_economico')
            ->get();

        $usuariosDisponibles = User::query()
            ->whereDoesntHave('personal')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $categoriasPersonal = ['OPERATIVO', 'ADMINISTRATIVO'];

        return view('admin.settings.personal.create', compact('unidades', 'turnos', 'patrullas', 'usuariosDisponibles', 'categoriasPersonal'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'user_id' => 'nullable|exists:users,id|unique:personals,user_id',

            'nombre' => 'required|string|max:100',
            'ap_paterno' => 'nullable|string|max:100',
            'ap_materno' => 'nullable|string|max:100',

            'curp' => 'nullable|string|max:18|unique:personals,curp',
            'rfc' => 'nullable|string|max:13',

            'cuip' => 'nullable|string|max:30|unique:personals,cuip',

            'grado' => 'nullable|string|max:120',
            'puesto' => 'nullable|string|max:120',

            'adscripcion' => 'nullable|string|max:200',
            'area' => 'nullable|string|max:200',

            'categoria' => 'required|in:OPERATIVO,ADMINISTRATIVO',

            'estatus' => 'required|string|max:30',

            'fecha_ingreso' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
        ]);

        try {
            if (!empty($validated['patrulla_id'])) {
                $patrulla = Patrulla::query()
                    ->where('id', $validated['patrulla_id'])
                    ->where('activa', 1)
                    ->first();

                if (!$patrulla) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no está activa.'])
                        ->withInput();
                }

                if ((int) $patrulla->unidad_id !== (int) $validated['unidad_id']) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no pertenece a la misma unidad.'])
                        ->withInput();
                }

                $ocupada = Personal::query()
                    ->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('patrulla_id', $validated['patrulla_id'])
                    ->exists();

                if ($ocupada) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'Esa patrulla ya está asignada a otro elemento ACTIVO.'])
                        ->withInput();
                }
            }

            Personal::create($validated);

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al crear el personal. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function show(Personal $personal)
    {
        $personal->load([
            'user',
            'unidad',
            'turno',
            'patrulla',
            'rolesServicio',
            'incidencias',
            'documentos',
            'asignaciones',
            'contactos',
            'domicilios',
            'emergencias',
            'asignaciones.armamento',
        ]);

        $asignacionesArmamentoActivas = PersonalAsignacion::query()
            ->where('personal_id', $personal->id)
            ->whereNotNull('armamento_id')
            ->whereNull('fecha_fin')
            ->with('armamento')
            ->orderByDesc('fecha_asignacion')
            ->get();

        $armamentosDisponibles = Armamento::query()
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', $personal->unidad_id)
            ->whereDoesntHave('asignaciones', function ($q) {
                $q->whereNull('fecha_fin');
            })
            ->orderBy('tipo')
            ->orderBy('clase')
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get();

        return view('admin.settings.personal.show', compact(
            'personal',
            'armamentosDisponibles',
            'asignacionesArmamentoActivas'
        ));
    }

    public function edit(Personal $personal)
    {
        $unidades = Unidad::query()->where('activa', 1)->orderBy('nombre')->get();
        $turnos = Turno::query()->where('activo', 1)->orderBy('nombre')->get();

        $patrullas = Patrulla::query()
            ->where('activa', 1)
            ->where('unidad_id', $personal->unidad_id)
            ->whereDoesntHave('personal', function ($q) use ($personal) {
                $q->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('id', '!=', $personal->id);
            })
            ->orderBy('numero_economico')
            ->get();

        $usuariosDisponibles = User::query()
            ->where(function ($q) use ($personal) {
                $q->whereDoesntHave('personal');

                if ($personal->user_id) {
                    $q->orWhere('id', $personal->user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $categoriasPersonal = ['OPERATIVO', 'ADMINISTRATIVO'];

        return view('admin.settings.personal.edit', compact('personal', 'unidades', 'turnos', 'patrullas', 'usuariosDisponibles', 'categoriasPersonal'));
    }

    public function update(Request $request, Personal $personal)
    {
        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'user_id' => 'nullable|exists:users,id|unique:personals,user_id,' . $personal->id,

            'nombre' => 'required|string|max:100',
            'ap_paterno' => 'nullable|string|max:100',
            'ap_materno' => 'nullable|string|max:100',

            'curp' => 'nullable|string|max:18|unique:personals,curp,' . $personal->id,
            'rfc' => 'nullable|string|max:13',

            'cuip' => 'nullable|string|max:30|unique:personals,cuip,' . $personal->id,

            'grado' => 'nullable|string|max:120',
            'puesto' => 'nullable|string|max:120',

            'adscripcion' => 'nullable|string|max:200',
            'area' => 'nullable|string|max:200',

            'categoria' => 'required|in:OPERATIVO,ADMINISTRATIVO',

            'estatus' => 'required|string|max:30',

            'fecha_ingreso' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
        ]);

        try {
            if (!empty($validated['patrulla_id'])) {
                $patrulla = Patrulla::query()
                    ->where('id', $validated['patrulla_id'])
                    ->where('activa', 1)
                    ->first();

                if (!$patrulla) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no está activa.'])
                        ->withInput();
                }

                if ((int) $patrulla->unidad_id !== (int) $validated['unidad_id']) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no pertenece a la misma unidad.'])
                        ->withInput();
                }

                $ocupada = Personal::query()
                    ->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('id', '!=', $personal->id)
                    ->where('patrulla_id', $validated['patrulla_id'])
                    ->exists();

                if ($ocupada) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'Esa patrulla ya está asignada a otro elemento ACTIVO.'])
                        ->withInput();
                }
            }

            $personal->update($validated);

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al actualizar el personal. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal)
    {
        try {
            $personal->delete();

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el personal. Inténtelo nuevamente.');
        }
    }
}
