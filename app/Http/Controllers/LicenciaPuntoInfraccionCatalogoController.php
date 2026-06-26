<?php

namespace App\Http\Controllers;

use App\Models\LicenciaPuntoInfraccion;
use App\Models\LicenciaPuntoCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LicenciaPuntoInfraccionCatalogoController extends Controller
{
    public function index()
    {
        $infracciones = LicenciaPuntoInfraccion::query()
            ->withCount('movimientos')
            ->get();
        $infracciones = $this->ordenarInfracciones($infracciones);

        return view('admin.settings.licencias_puntos.infracciones.index', compact('infracciones'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        LicenciaPuntoInfraccion::create($validated);

        return redirect()
            ->route('settings.licencias_puntos.infracciones.index')
            ->with('success', 'Penalización agregada correctamente.');
    }

    public function update(Request $request, LicenciaPuntoInfraccion $infraccion)
    {
        $validated = $this->validateData($request, $infraccion);

        $infraccion->update($validated);

        return redirect()
            ->route('settings.licencias_puntos.infracciones.index')
            ->with('success', 'Penalización actualizada correctamente.');
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
            'articulo' => ['nullable', 'string', 'max:30'],
            'fraccion' => ['nullable', 'string', 'max:30'],
            'inciso' => ['nullable', 'string', 'max:30'],
            'puntos' => ['required', 'integer', 'min:0', 'max:' . LicenciaPuntoCuenta::SALDO_MAXIMO],
            'multa_uma_min' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'multa_uma_max' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'retencion_vehiculo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'fundamento_legal' => ['nullable', 'string'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['articulo'] = $this->normalizarCampoCorto($validated['articulo'] ?? null);
        $validated['fraccion'] = $this->normalizarCampoCorto($validated['fraccion'] ?? null, true);
        $validated['inciso'] = $this->normalizarCampoCorto($validated['inciso'] ?? null);
        $validated['puntos'] = (int) $validated['puntos'];
        $validated['multa_uma_min'] = array_key_exists('multa_uma_min', $validated) && $validated['multa_uma_min'] !== null
            ? (int) $validated['multa_uma_min']
            : null;
        $validated['multa_uma_max'] = array_key_exists('multa_uma_max', $validated) && $validated['multa_uma_max'] !== null
            ? (int) $validated['multa_uma_max']
            : null;
        $validated['retencion_vehiculo'] = $request->boolean('retencion_vehiculo');
        $validated['descripcion'] = isset($validated['descripcion']) && trim((string) $validated['descripcion']) !== ''
            ? trim((string) $validated['descripcion'])
            : null;

        if (
            $validated['multa_uma_min'] !== null
            && $validated['multa_uma_max'] !== null
            && $validated['multa_uma_min'] > $validated['multa_uma_max']
        ) {
            throw ValidationException::withMessages([
                'multa_uma_max' => 'La UMA maxima no puede ser menor que la UMA minima.',
            ]);
        }

        if ($validated['puntos'] <= 0 && !$validated['retencion_vehiculo']) {
            throw ValidationException::withMessages([
                'puntos' => 'Captura al menos puntos a descontar o marca retiro de vehiculo.',
            ]);
        }

        $validated['fundamento_legal'] = $this->fundamentoLegal($validated);
        $validated['activa'] = $request->boolean('activa');

        return $validated;
    }

    private function normalizarCodigo(string $value): string
    {
        $codigo = Str::slug($value, '_');
        $codigo = mb_strtoupper($codigo, 'UTF-8');

        return $codigo !== '' ? mb_substr($codigo, 0, 50, 'UTF-8') : 'PENALIZACION';
    }

    private function normalizarCampoCorto($value, bool $upper = false): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $upper ? mb_strtoupper($value, 'UTF-8') : $value;
    }

    private function fundamentoLegal(array $data): string
    {
        $fundamento = trim((string) ($data['fundamento_legal'] ?? ''));

        if ($fundamento !== '') {
            return $fundamento;
        }

        $referencia = $this->referenciaLegal($data);
        $sanciones = [];

        if ($data['multa_uma_min'] || $data['multa_uma_max']) {
            $sanciones[] = 'multa de ' . $this->multaUmaTexto($data);
        }

        if ((int) $data['puntos'] > 0) {
            $puntos = (int) $data['puntos'];
            $sanciones[] = $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos') . ' de penalizacion en la licencia para conducir';
        }

        if (!empty($data['retencion_vehiculo'])) {
            $sanciones[] = 'retiro de vehiculo';
        }

        if ($referencia !== '' && $sanciones !== []) {
            return $referencia . ': ' . implode(' y ', $sanciones) . '.';
        }

        if ($referencia !== '') {
            return $referencia . '.';
        }

        return 'Fundamentado en el Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.';
    }

    private function referenciaLegal(array $data): string
    {
        $partes = [];

        if (!empty($data['articulo'])) {
            $partes[] = 'Articulo ' . $data['articulo'];
        }

        if (!empty($data['fraccion'])) {
            $partes[] = 'fraccion ' . $data['fraccion'];
        }

        if (!empty($data['inciso'])) {
            $partes[] = 'inciso ' . $data['inciso'];
        }

        return implode(', ', $partes);
    }

    private function multaUmaTexto(array $data): string
    {
        $min = $data['multa_uma_min'] ?? null;
        $max = $data['multa_uma_max'] ?? null;

        if ($min && $max) {
            return $min === $max ? $min . ' UMAS' : $min . ' a ' . $max . ' UMAS';
        }

        if ($min) {
            return $min . ' UMAS';
        }

        return 'hasta ' . $max . ' UMAS';
    }

    private function ordenarInfracciones($infracciones)
    {
        return $infracciones->sortBy(function (LicenciaPuntoInfraccion $infraccion) {
            return implode('|', [
                $infraccion->activa ? '0' : '1',
                $infraccion->articulo ? str_pad((string) $infraccion->articulo, 8, '0', STR_PAD_LEFT) : 'ZZZZZZZZ',
                $infraccion->fraccion ?: 'ZZZZ',
                $infraccion->inciso ?: 'ZZZZ',
                $infraccion->nombre,
            ]);
        })->values();
    }
}
