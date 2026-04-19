<?php

namespace App\Http\Controllers;

use App\Models\Armamento;
use App\Models\Personal;
use App\Models\PersonalAsignacion;
use App\Models\PersonalDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PersonalAsignacionController extends Controller
{
    public function create(Personal $personal)
    {
        return redirect()->route('personal.show', $personal->id);
    }

    public function edit(Personal $personal, PersonalAsignacion $asignacion)
    {
        if ((int) $asignacion->personal_id !== (int) $personal->id) {
            abort(404);
        }

        return redirect()->route('personal.show', $personal->id);
    }

    public function store(Request $request, Personal $personal)
    {
        try {
            $validated = $this->datosValidados($request, $personal);

            DB::beginTransaction();

            $this->validarArmamentoDisponible($personal, $validated);
            $this->validarDocumentoDelPersonal($personal, $validated['documento_id'] ?? null);

            $asignacion = PersonalAsignacion::create($this->datosParaGuardar($personal, $validated));

            DB::commit();

            Log::info('Asignacion de personal creada', [
                'id' => $asignacion->id,
                'personal_id' => $personal->id,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Asignación registrada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar asignación de personal: ' . $e->getMessage());

            return back()
                ->withErrors('Hubo un error al registrar la asignación. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function update(Request $request, Personal $personal, PersonalAsignacion $asignacion)
    {
        if ((int) $asignacion->personal_id !== (int) $personal->id) {
            abort(404);
        }

        try {
            $validated = $this->datosValidados($request, $personal, $asignacion);

            DB::beginTransaction();

            $this->validarArmamentoDisponible($personal, $validated, $asignacion);
            $this->validarDocumentoDelPersonal($personal, $validated['documento_id'] ?? null);

            $asignacion->update($this->datosParaGuardar($personal, $validated));

            DB::commit();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Asignación actualizada correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar asignación de personal: ' . $e->getMessage());

            return back()
                ->withErrors('Hubo un error al actualizar la asignación. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function cerrar(Request $request, Personal $personal, PersonalAsignacion $asignacion)
    {
        if ((int) $asignacion->personal_id !== (int) $personal->id) {
            abort(404);
        }

        $validated = $request->validate([
            'fecha_fin' => 'nullable|date',
        ]);

        try {
            $asignacion->update([
                'fecha_fin' => $validated['fecha_fin'] ?? now()->toDateString(),
                'activo' => 0,
            ]);

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Asignación cerrada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al cerrar asignación de personal: ' . $e->getMessage());

            return back()->withErrors('Hubo un error al cerrar la asignación.');
        }
    }

    public function destroy(Personal $personal, PersonalAsignacion $asignacion)
    {
        if ((int) $asignacion->personal_id !== (int) $personal->id) {
            abort(404);
        }

        try {
            $asignacion->delete();

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Asignación eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar asignación de personal: ' . $e->getMessage());

            return back()->withErrors('Hubo un error al eliminar la asignación.');
        }
    }

    public function asignarArmamento(Request $request, Personal $personal)
    {
        return $this->store($request, $personal);
    }

    public function quitarArmamento(Request $request, Personal $personal, PersonalAsignacion $asignacion)
    {
        return $this->cerrar($request, $personal, $asignacion);
    }

    private function datosValidados(Request $request, Personal $personal, ?PersonalAsignacion $asignacion = null): array
    {
        $validated = $request->validate([
            'armamento_id' => 'nullable|integer|exists:armamentos,id',
            'fecha_asignacion' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_asignacion',
            'folio' => 'nullable|string|max:80',
            'documento_id' => 'nullable|integer|exists:personal_documentos,id',
            'observaciones' => 'nullable|string|max:5000',
            'comisionado_a' => 'nullable|string|max:255',
            'ubicacion_interna' => 'nullable|string|max:255',
            'municipio_localidad_servicio' => 'nullable|string|max:255',
            'funciones' => 'nullable|string|max:255',
            'actividades' => 'nullable|string|max:5000',
            'horario' => 'nullable|string|max:255',
            'tipo_contratacion' => 'nullable|in:BASE,INTERINATO',
            'dpc' => 'nullable|string|max:255',
            'oficina_pago' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $campo => $valor) {
            if (is_string($valor)) {
                $validated[$campo] = trim($valor);
                if ($validated[$campo] === '') {
                    $validated[$campo] = null;
                }
            }
        }

        $camposConDato = [
            'armamento_id',
            'folio',
            'documento_id',
            'observaciones',
            'comisionado_a',
            'ubicacion_interna',
            'municipio_localidad_servicio',
            'funciones',
            'actividades',
            'horario',
            'tipo_contratacion',
            'dpc',
            'oficina_pago',
        ];

        $tieneContenido = collect($camposConDato)->contains(function ($campo) use ($validated) {
            return !empty($validated[$campo]);
        });

        if (!$tieneContenido && !$asignacion) {
            throw ValidationException::withMessages([
                'armamento_id' => 'Registra al menos un dato de asignación o selecciona un armamento.',
            ]);
        }

        return $validated;
    }

    private function datosParaGuardar(Personal $personal, array $validated): array
    {
        return [
            'personal_id' => $personal->id,
            'armamento_id' => $validated['armamento_id'] ?? null,
            'fecha_asignacion' => $validated['fecha_asignacion'],
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'folio' => $validated['folio'] ?? null,
            'documento_id' => $validated['documento_id'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'activo' => empty($validated['fecha_fin']) ? 1 : 0,
            'comisionado_a' => $validated['comisionado_a'] ?? null,
            'ubicacion_interna' => $validated['ubicacion_interna'] ?? null,
            'municipio_localidad_servicio' => $validated['municipio_localidad_servicio'] ?? null,
            'funciones' => $validated['funciones'] ?? null,
            'actividades' => $validated['actividades'] ?? null,
            'horario' => $validated['horario'] ?? null,
            'tipo_contratacion' => $validated['tipo_contratacion'] ?? null,
            'dpc' => $validated['dpc'] ?? null,
            'oficina_pago' => $validated['oficina_pago'] ?? null,
        ];
    }

    private function validarArmamentoDisponible(Personal $personal, array $validated, ?PersonalAsignacion $asignacion = null): void
    {
        if (empty($validated['armamento_id'])) {
            return;
        }

        $arma = Armamento::query()
            ->where('id', $validated['armamento_id'])
            ->where('unidad_id', $personal->unidad_id)
            ->whereIn('estatus', ['ACTIVO', '1', 1])
            ->first();

        if (!$arma) {
            throw ValidationException::withMessages([
                'armamento_id' => 'El armamento seleccionado no es válido, no está ACTIVO o no pertenece a la unidad del elemento.',
            ]);
        }

        $ocupada = PersonalAsignacion::query()
            ->where('armamento_id', $arma->id)
            ->whereNull('fecha_fin')
            ->where('activo', 1)
            ->when($asignacion, function ($q) use ($asignacion) {
                $q->where('id', '!=', $asignacion->id);
            })
            ->exists();

        if ($ocupada) {
            throw ValidationException::withMessages([
                'armamento_id' => 'Ese armamento ya está asignado a otro elemento.',
            ]);
        }

        $tipo = strtoupper(trim((string) ($arma->tipo ?? '')));

        if (!$this->contiene($tipo, 'CORTA') && !$this->contiene($tipo, 'LARGA')) {
            throw ValidationException::withMessages([
                'armamento_id' => 'El armamento no tiene TIPO válido (ARMA CORTA / ARMA LARGA).',
            ]);
        }

        $yaTieneMismoTipo = PersonalAsignacion::query()
            ->join('armamentos', 'armamentos.id', '=', 'personal_asignacions.armamento_id')
            ->where('personal_asignacions.personal_id', $personal->id)
            ->whereNull('personal_asignacions.fecha_fin')
            ->where('personal_asignacions.activo', 1)
            ->where('armamentos.tipo', $arma->tipo)
            ->when($asignacion, function ($q) use ($asignacion) {
                $q->where('personal_asignacions.id', '!=', $asignacion->id);
            })
            ->exists();

        if ($yaTieneMismoTipo) {
            $mensaje = $this->contiene($tipo, 'CORTA')
                ? 'Este elemento ya tiene un arma CORTA activa.'
                : 'Este elemento ya tiene un arma LARGA activa.';

            throw ValidationException::withMessages([
                'armamento_id' => $mensaje,
            ]);
        }
    }

    private function contiene(string $texto, string $busqueda): bool
    {
        return strpos($texto, $busqueda) !== false;
    }

    private function validarDocumentoDelPersonal(Personal $personal, ?int $documentoId): void
    {
        if (!$documentoId) {
            return;
        }

        $existe = PersonalDocumento::query()
            ->where('id', $documentoId)
            ->where('personal_id', $personal->id)
            ->exists();

        if (!$existe) {
            throw ValidationException::withMessages([
                'documento_id' => 'El documento seleccionado no pertenece a este elemento.',
            ]);
        }
    }
}
