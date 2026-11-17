<?php

namespace App\Http\Controllers;

use App\Models\ArchivoAnaquel;
use App\Models\ArchivoArea;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoAnaquelController extends Controller
{
    public function index(Request $request)
    {
        $anaqueles = ArchivoAnaquel::with('area')
            ->withCount('bloques')
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', (int) $request->input('area_id')))
            ->orderBy('area_id')->orderBy('codigo')->paginate(30)->withQueryString();
        $areas = ArchivoArea::orderBy('nombre')->get();
        return view('admin.settings.archivo_siniestros.anaqueles.index', compact('anaqueles', 'areas'));
    }

    public function mapa(Request $request)
    {
        $areas = ArchivoArea::where('activo', true)
            ->with(['anaqueles' => fn ($q) => $q->where('activo', true)->orderBy('codigo'),
                'anaqueles.bloques' => fn ($q) => $q->where('activo', true)->orderBy('fila')->orderBy('columna')])
            ->get();
        $bloques = $areas->flatMap(fn ($area) => $area->anaqueles)
            ->flatMap(fn ($anaquel) => $anaquel->bloques);
        $ids = $bloques->pluck('id')->all();
        $ocupaciones = ArchivoLibro::whereIn('bloque_actual_id', $ids)
            ->selectRaw('bloque_actual_id, count(*) as total')
            ->groupBy('bloque_actual_id')->pluck('total', 'bloque_actual_id');
        $reservas = ArchivoLibro::whereIn('bloque_destino_id', $ids)
            ->where(function ($q) {
                $q->whereNull('bloque_actual_id')
                    ->orWhereColumn('bloque_actual_id', '<>', 'bloque_destino_id');
            })
            ->selectRaw('bloque_destino_id, count(*) as total')
            ->groupBy('bloque_destino_id')->pluck('total', 'bloque_destino_id');

        return view('admin.settings.archivo_siniestros.anaqueles.mapa', compact('areas', 'ocupaciones', 'reservas'));
    }

    public function create()
    {
        $areas = ArchivoArea::where('activo', true)->orderBy('nombre')->get();
        return view('admin.settings.archivo_siniestros.anaqueles.create', compact('areas'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        ArchivoAnaquel::create($datos);
        return redirect()->route('archivo_siniestros.anaqueles.index')->with('success', 'Anaquel registrado correctamente.');
    }

    public function edit(ArchivoAnaquel $anaquel)
    {
        $areas = ArchivoArea::orderBy('nombre')->get();
        return view('admin.settings.archivo_siniestros.anaqueles.edit', compact('anaquel', 'areas'));
    }

    public function update(Request $request, ArchivoAnaquel $anaquel)
    {
        $datos = $request->validate($this->reglas($anaquel));
        if ($anaquel->bloques()->exists() && (int) $anaquel->area_id !== (int) $datos['area_id']) {
            return back()->withErrors(['area_id' => 'No puedes mover un anaquel que tiene bloques.'])->withInput();
        }
        if ($anaquel->bloques()->where('fila', '>', $datos['total_filas'])->exists()
            || $anaquel->bloques()->where('columna', '>', $datos['total_columnas'])->exists()) {
            return back()->withErrors(['total_filas' => 'Las nuevas dimensiones dejarían bloques fuera de la cuadrícula.'])->withInput();
        }
        if (!(bool) ($datos['activo'] ?? true) && $anaquel->bloques()->exists()) {
            return back()->withErrors(['activo' => 'No puedes desactivar un anaquel que todavía tiene bloques.'])->withInput();
        }
        $anaquel->update($datos);
        return redirect()->route('archivo_siniestros.anaqueles.index')->with('success', 'Anaquel actualizado correctamente.');
    }

    private function reglas(?ArchivoAnaquel $anaquel = null): array
    {
        return [
            'area_id' => ['required', Rule::exists('archivo_areas', 'id')->where('activo', true)],
            'codigo' => ['required', 'string', 'max:30', Rule::unique('archivo_anaqueles', 'codigo')
                ->where('area_id', request('area_id'))->ignore($anaquel?->id)],
            'nombre' => ['nullable', 'string', 'max:150'],
            'total_filas' => ['required', 'integer', 'min:1', 'max:100'],
            'total_columnas' => ['required', 'integer', 'min:1', 'max:100'],
            'plano_x' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_y' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_ancho' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'plano_alto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'rotacion' => ['required', 'integer', 'min:0', 'max:359'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
