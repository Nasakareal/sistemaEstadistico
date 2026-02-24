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
                ->where('estatus', 'ACTIVO')
                ->first();

            if (!$arma) {
                DB::rollBack();
                return back()->withErrors(['armamento_id' => 'El armamento seleccionado no es válido o no está ACTIVO.'])->withInput();
            }

            $ocupada = PersonalAsignacion::query()
                ->where('armamento_id', $arma->id)
                ->whereNull('fecha_fin')
                ->where('activo', 1)
                ->exists();

            if ($ocupada) {
                DB::rollBack();
                return back()->withErrors(['armamento_id' => 'Ese armamento ya está asignado a otro elemento.'])->withInput();
            }

            $clase = strtoupper((string)($arma->clase ?? ''));

            $data = [
                'personal_id' => $personal->id,
                'parque_vehicular_id' => null,
                'armamento_id' => $arma->id,
                'arma_corta_id' => null,
                'arma_larga_id' => null,
                'fecha_asignacion' => $validated['fecha_asignacion'],
                'fecha_fin' => null,
                'folio' => null,
                'documento_id' => null,
                'observaciones' => $validated['observaciones'] ?? null,
                'activo' => 1,
            ];

            if ($clase === 'CORTA') {
                $yaTieneCorta = PersonalAsignacion::query()
                    ->where('personal_id', $personal->id)
                    ->whereNull('fecha_fin')
                    ->where('activo', 1)
                    ->whereNotNull('arma_corta_id')
                    ->exists();

                if ($yaTieneCorta) {
                    DB::rollBack();
                    return back()->withErrors(['armamento_id' => 'Este elemento ya tiene un arma CORTA activa.'])->withInput();
                }

                $data['arma_corta_id'] = $arma->id;
            } elseif ($clase === 'LARGA') {
                $yaTieneLarga = PersonalAsignacion::query()
                    ->where('personal_id', $personal->id)
                    ->whereNull('fecha_fin')
                    ->where('activo', 1)
                    ->whereNotNull('arma_larga_id')
                    ->exists();

                if ($yaTieneLarga) {
                    DB::rollBack();
                    return back()->withErrors(['armamento_id' => 'Este elemento ya tiene un arma LARGA activa.'])->withInput();
                }

                $data['arma_larga_id'] = $arma->id;
            } else {
                DB::rollBack();
                return back()->withErrors(['armamento_id' => 'El armamento no tiene clase válida (CORTA/LARGA).'])->withInput();
            }

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
