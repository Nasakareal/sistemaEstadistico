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
        $ambitosVehiculo = LicenciaPuntoInfraccion::ambitosVehiculo();

        return view('admin.settings.licencias_puntos.infracciones.index', compact('infracciones', 'ambitosVehiculo'));
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
            'ambito_vehiculo' => ['nullable', 'string', Rule::in(array_keys(LicenciaPuntoInfraccion::ambitosVehiculo()))],
            'puntos' => ['required', 'integer', 'min:0', 'max:' . LicenciaPuntoCuenta::SALDO_MAXIMO],
            'multa_uma_min' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'multa_uma_max' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'amonestacion' => ['nullable', 'boolean'],
            'arresto_persona' => ['nullable', 'boolean'],
            'deposito_si_sin_persona_habilitada' => ['nullable', 'boolean'],
            'retencion_vehiculo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'fundamento_legal' => ['nullable', 'string'],
            'activa' => ['nullable', 'boolean'],
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['articulo'] = $this->normalizarCampoCorto($validated['articulo'] ?? null);
        $validated['fraccion'] = $this->normalizarCampoCorto($validated['fraccion'] ?? null, true);
        $validated['inciso'] = $this->normalizarCampoCorto($validated['inciso'] ?? null);
        $validated['ambito_vehiculo'] = $validated['ambito_vehiculo'] ?? 'general';
        $validated['puntos'] = (int) $validated['puntos'];
        $validated['multa_uma_min'] = null;
        $validated['multa_uma_max'] = null;
        $validated['amonestacion'] = $request->boolean('amonestacion');
        $validated['arresto_persona'] = $request->boolean('arresto_persona');
        $validated['deposito_si_sin_persona_habilitada'] = $validated['arresto_persona']
            || $request->boolean('deposito_si_sin_persona_habilitada');
        $validated['retencion_vehiculo'] = $request->boolean('retencion_vehiculo');
        $validated['descripcion'] = isset($validated['descripcion']) && trim((string) $validated['descripcion']) !== ''
            ? trim((string) $validated['descripcion'])
            : null;

        if (
            $validated['puntos'] <= 0
            && !$validated['amonestacion']
            && !$validated['arresto_persona']
            && !$validated['retencion_vehiculo']
            && !$validated['deposito_si_sin_persona_habilitada']
        ) {
            throw ValidationException::withMessages([
                'puntos' => 'Captura al menos puntos a descontar, amonestacion, arresto o deposito.',
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

        if (!empty($data['amonestacion'])) {
            $sanciones[] = 'amonestacion a la persona';
        }

        if (!empty($data['arresto_persona'])) {
            $sanciones[] = 'arresto de la persona';
        }

        if ((int) $data['puntos'] > 0) {
            $puntos = (int) $data['puntos'];
            $sanciones[] = $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos') . ' de penalizacion en la licencia para conducir';
        }

        if (!empty($data['retencion_vehiculo'])) {
            $sanciones[] = 'remision o retiro del vehiculo al deposito';
        } elseif (!empty($data['deposito_si_sin_persona_habilitada'])) {
            $sanciones[] = 'deposito del vehiculo cuando no se encuentre persona legalmente habilitada para hacerse cargo inmediato';
        }

        if ($referencia !== '' && $sanciones !== []) {
            return $referencia . ': ' . implode('; ', $sanciones) . '.';
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
                $infraccion->ambito_vehiculo ?: 'general',
                $infraccion->fraccion ?: 'ZZZZ',
                $infraccion->inciso ?: 'ZZZZ',
                $infraccion->nombre,
            ]);
        })->values();
    }
}
