<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalAsignacion;
use App\Models\Armamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PersonalAsignacionController extends Controller
{
    public function store(Request $request, Personal $personal)
    {
        Log::info('ENTRO store armamento', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'db' => DB::connection()->getDatabaseName(),
            'personal_id' => $personal->id,
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'armamento_id' => 'required|integer',
            'fecha_asignacion' => 'required|date',
            'observaciones' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $arma = Armamento::query()
                ->where('id', $validated['armamento_id'])
                ->whereIn('estatus', ['ACTIVO', '1', 1])
                ->first();

            if (!$arma) {
                DB::rollBack();
                Log::warning('NO SE PUDO ASIGNAR: armamento no válido o no ACTIVO', [
                    'armamento_id' => $validated['armamento_id'],
                    'db' => DB::connection()->getDatabaseName(),
                ]);
                return back()->withErrors(['armamento_id' => 'El armamento seleccionado no es válido o no está ACTIVO.'])->withInput();
            }

            $ocupada = PersonalAsignacion::query()
                ->where('armamento_id', $arma->id)
                ->whereNull('fecha_fin')
                ->where('activo', 1)
                ->exists();

            if ($ocupada) {
                DB::rollBack();
                Log::warning('NO SE PUDO ASIGNAR: armamento ya ocupado', [
                    'armamento_id' => $arma->id,
                    'db' => DB::connection()->getDatabaseName(),
                ]);
                return back()->withErrors(['armamento_id' => 'Ese armamento ya está asignado a otro elemento.'])->withInput();
            }

            $tipo = strtoupper(trim((string)($arma->tipo ?? '')));

            if (!str_contains($tipo, 'CORTA') && !str_contains($tipo, 'LARGA')) {
                DB::rollBack();
                Log::warning('NO SE PUDO ASIGNAR: tipo inválido (se esperaba ARMA CORTA/LARGA)', [
                    'armamento_id' => $arma->id,
                    'tipo' => $arma->tipo,
                    'clase' => $arma->clase,
                    'db' => DB::connection()->getDatabaseName(),
                ]);
                return back()->withErrors(['armamento_id' => 'El armamento no tiene TIPO válido (ARMA CORTA / ARMA LARGA).'])->withInput();
            }

            $yaTieneMismoTipo = PersonalAsignacion::query()
                ->join('armamentos', 'armamentos.id', '=', 'personal_asignacions.armamento_id')
                ->where('personal_asignacions.personal_id', $personal->id)
                ->whereNull('personal_asignacions.fecha_fin')
                ->where('personal_asignacions.activo', 1)
                ->where('armamentos.tipo', $arma->tipo)
                ->exists();

            if ($yaTieneMismoTipo) {
                DB::rollBack();
                $msg = str_contains($tipo, 'CORTA')
                    ? 'Este elemento ya tiene un arma CORTA activa.'
                    : 'Este elemento ya tiene un arma LARGA activa.';

                Log::warning('NO SE PUDO ASIGNAR: ya tiene arma del mismo tipo activa', [
                    'personal_id' => $personal->id,
                    'armamento_id' => $arma->id,
                    'tipo' => $arma->tipo,
                    'db' => DB::connection()->getDatabaseName(),
                ]);

                return back()->withErrors(['armamento_id' => $msg])->withInput();
            }

            $data = [
                'personal_id' => $personal->id,
                'armamento_id' => $arma->id,
                'fecha_asignacion' => $validated['fecha_asignacion'],
                'fecha_fin' => null,
                'folio' => null,
                'documento_id' => null,
                'observaciones' => $validated['observaciones'] ?? null,
                'activo' => 1,
            ];

            $asig = PersonalAsignacion::create($data);

            DB::commit();

            Log::info('ASIGNACION ARMAMENTO CREADA', [
                'id' => $asig->id,
                'db' => DB::connection()->getDatabaseName(),
            ]);

            return redirect()->route('personal.show', $personal->id)->with('success', 'Armamento asignado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ERROR store armamento', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'db' => DB::connection()->getDatabaseName(),
            ]);

            return back()->withErrors($e->getMessage())->withInput();
        }
    }
}
