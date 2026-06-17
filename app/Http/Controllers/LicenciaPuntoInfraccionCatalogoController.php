<?php

namespace App\Http\Controllers;

use App\Models\LicenciaPuntoInfraccion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LicenciaPuntoInfraccionCatalogoController extends Controller
{
    public function index()
    {
        $infracciones = LicenciaPuntoInfraccion::query()
            ->withCount('movimientos')
            ->orderByDesc('activa')
            ->orderBy('nombre')
            ->get();

        return view('admin.settings.licencias_puntos.infracciones.index', compact('infracciones'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        LicenciaPuntoInfraccion::create($validated);

        return redirect()
            ->route('settings.licencias_puntos.infracciones.index')
            ->with('success', 'Infraccion agregada correctamente.');
    }

    public function update(Request $request, LicenciaPuntoInfraccion $infraccion)
    {
        $validated = $this->validateData($request, $infraccion);

        $infraccion->update($validated);

        return redirect()
            ->route('settings.licencias_puntos.infracciones.index')
            ->with('success', 'Infraccion actualizada correctamente.');
    }

    private function validateData(Request $request, ?LicenciaPuntoInfraccion $infraccion = null): array
    {
        $codigo = $request->input('codigo');
        if (trim((string) $codigo) === '') {
            $codigo = $request->input('nombre');
        }

        $request->merge([
            'codigo' => $this->normalizarCodigo((string) $codigo),
        ]);

        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('licencia_punto_infracciones', 'codigo')->ignore(optional($infraccion)->id),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'puntos' => ['required', 'integer', 'min:1', 'max:8'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['descripcion'] = isset($validated['descripcion']) && trim((string) $validated['descripcion']) !== ''
            ? trim((string) $validated['descripcion'])
            : null;
        $validated['activa'] = $request->boolean('activa');

        return $validated;
    }

    private function normalizarCodigo(string $value): string
    {
        $codigo = Str::slug($value, '_');
        $codigo = mb_strtoupper($codigo, 'UTF-8');

        return $codigo !== '' ? $codigo : 'INFRACCION';
    }
}
