<?php

namespace App\Http\Controllers;

use App\Models\ArchivoAnaquel;
use App\Models\ArchivoBloque;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArchivoBloqueController extends Controller
{
    public function index(Request $request)
    {
        $bloques = ArchivoBloque::with('anaquel.area')
            ->when($request->filled('anaquel_id'), fn ($q) => $q->where('anaquel_id', (int) $request->input('anaquel_id')))
            ->orderBy('anaquel_id')->orderBy('fila')->orderBy('columna')
            ->paginate(30)->withQueryString();
        $anaqueles = ArchivoAnaquel::with('area')->orderBy('codigo')->get();
        return view('admin.settings.archivo_siniestros.bloques.index', compact('bloques', 'anaqueles'));
    }

    public function porQr(string $uuid)
    {
        $bloque = ArchivoBloque::with('anaquel.area')
            ->where('qr_uuid', $uuid)->firstOrFail();
        return redirect()->route('archivo_siniestros.mapa', ['bloque_id' => $bloque->id]);
    }

    public function create()
    {
        $anaqueles = ArchivoAnaquel::where('activo', true)->with('area')->orderBy('codigo')->get();
        return view('admin.settings.archivo_siniestros.bloques.create', compact('anaqueles'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());
        $this->validarCuadricula($datos);
        $datos['qr_uuid'] = (string) Str::uuid();
        ArchivoBloque::create($datos);
        return redirect()->route('archivo_siniestros.bloques.index')->with('success', 'Bloque registrado correctamente.');
    }

    public function edit(ArchivoBloque $bloque)
    {
        $anaqueles = ArchivoAnaquel::with('area')->orderBy('codigo')->get();
        return view('admin.settings.archivo_siniestros.bloques.edit', compact('bloque', 'anaqueles'));
    }

    public function update(Request $request, ArchivoBloque $bloque)
    {
        $datos = $request->validate($this->reglas($bloque));
        $this->validarCuadricula($datos);
        DB::transaction(function () use ($bloque, $datos) {
            $bloque = ArchivoBloque::whereKey($bloque->id)->lockForUpdate()->firstOrFail();
            $ocupacion = ArchivoLibro::where(function ($q) use ($bloque) {
                $q->where('bloque_actual_id', $bloque->id)
                    ->orWhere('bloque_destino_id', $bloque->id);
            })->count();
            if ($ocupacion && (int) $datos['anaquel_id'] !== (int) $bloque->anaquel_id) {
                throw ValidationException::withMessages(['anaquel_id' => 'No puedes cambiar de anaquel un bloque con libros.']);
            }
            if ($ocupacion > (int) $datos['capacidad_libros']) {
                throw ValidationException::withMessages(['capacidad_libros' => "Hay {$ocupacion} libros almacenados o reservados."]);
            }
            if ($ocupacion && !(bool) ($datos['activo'] ?? true)) {
                throw ValidationException::withMessages(['activo' => 'No puedes desactivar un bloque que contiene libros o reservas.']);
            }
            if (!$bloque->qr_uuid) {
                $datos['qr_uuid'] = (string) Str::uuid();
            }
            $bloque->update($datos);
        }, 3);
        return redirect()->route('archivo_siniestros.bloques.index')->with('success', 'Bloque actualizado correctamente.');
    }

    private function validarCuadricula(array $datos): void
    {
        $anaquel = ArchivoAnaquel::findOrFail($datos['anaquel_id']);
        if ((int) $datos['fila'] > $anaquel->total_filas || (int) $datos['columna'] > $anaquel->total_columnas) {
            throw ValidationException::withMessages(['fila' => 'La fila o columna supera las dimensiones del anaquel.']);
        }
    }

    private function reglas(?ArchivoBloque $bloque = null): array
    {
        $anaquelId = request('anaquel_id');
        return [
            'anaquel_id' => ['required', Rule::exists('archivo_anaqueles', 'id')->where('activo', true)],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('archivo_bloques', 'codigo')
                ->where('anaquel_id', $anaquelId)->ignore($bloque?->id)],
            'fila' => ['required', 'integer', 'min:1', 'max:100', Rule::unique('archivo_bloques', 'fila')
                ->where('anaquel_id', $anaquelId)->where('columna', request('columna'))->ignore($bloque?->id)],
            'columna' => ['required', 'integer', 'min:1', 'max:100'],
            'capacidad_libros' => ['required', 'integer', 'min:1', 'max:65535'],
            'orden_apilado' => ['required', Rule::in(['arriba_abajo', 'abajo_arriba'])],
            'activo' => ['required', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
