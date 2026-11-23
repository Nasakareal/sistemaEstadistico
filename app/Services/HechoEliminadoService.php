<?php

namespace App\Services;

use App\Models\HechoEliminado;
use App\Models\Hechos;
use App\Support\HechoAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class HechoEliminadoService
{
    public function archivar(Hechos $hecho, ?int $usuarioId): HechoEliminado
    {
        return DB::transaction(function () use ($hecho, $usuarioId) {
            $hecho = Hechos::query()->lockForUpdate()->findOrFail($hecho->id);
            $hecho->loadMissing(['vehiculos.conductores', 'vehiculos.servicios', 'lesionados', 'dictamen']);

            $croquis = Schema::hasTable('croquis')
                ? DB::table('croquis')->where('hecho_id', $hecho->id)->get()->map(fn ($fila) => (array) $fila)->all()
                : [];

            $archivo = HechoEliminado::create([
                'hecho_id_original' => $hecho->id,
                'unidad_org_id' => HechoAccess::effectiveUnidadIdForHecho($hecho),
                'datos_hecho' => $hecho->getAttributes(),
                'lesionados' => $hecho->lesionados->map(fn ($lesionado) => $lesionado->getAttributes())->all(),
                'croquis' => $croquis,
                'dictamen_id' => optional($hecho->dictamen)->id,
                'eliminado_por' => $usuarioId,
                'eliminado_at' => now(),
            ]);

            foreach ($hecho->vehiculos as $vehiculo) {
                $archivo->vehiculos()->create([
                    'vehiculo_id_original' => $vehiculo->id,
                    'datos_vehiculo' => $vehiculo->getAttributes(),
                    'datos_pivote' => $vehiculo->pivot ? $vehiculo->pivot->getAttributes() : null,
                    'conductores' => $vehiculo->conductores->map(function ($conductor) {
                        return [
                            'datos' => $conductor->getAttributes(),
                            'pivote' => $conductor->pivot ? $conductor->pivot->getAttributes() : null,
                        ];
                    })->all(),
                    'servicios' => $vehiculo->servicios->map(fn ($servicio) => $servicio->getAttributes())->all(),
                ]);
            }

            if ($hecho->dictamen) {
                $hecho->dictamen->update(['hecho_id' => null]);
            }

            $hecho->vehiculos()->detach();
            $hecho->lesionados()->delete();
            if (Schema::hasTable('croquis')) {
                DB::table('croquis')->where('hecho_id', $hecho->id)->delete();
            }
            $hecho->delete();

            return $archivo;
        });
    }

    public function restaurar(HechoEliminado $archivo, ?int $usuarioId): Hechos
    {
        return DB::transaction(function () use ($archivo, $usuarioId) {
            $archivo = HechoEliminado::query()
                ->with('vehiculos')
                ->lockForUpdate()
                ->findOrFail($archivo->id);

            if ($archivo->restaurado_at) {
                throw new RuntimeException('Este hecho ya fue restaurado.');
            }

            $datosHecho = $this->filtrarColumnas('hechos', $archivo->datos_hecho ?? []);
            $idOriginal = (int) $archivo->hecho_id_original;

            if (DB::table('hechos')->where('id', $idOriginal)->exists()) {
                unset($datosHecho['id']);
            } else {
                $datosHecho['id'] = $idOriginal;
            }

            try {
                $hechoId = $this->insertarRegistro('hechos', $datosHecho);
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    'No se pudo restaurar el hecho; posiblemente su folio o UUID ya está en uso. ' . $e->getMessage(),
                    0,
                    $e
                );
            }

            foreach ($archivo->vehiculos as $vehiculoArchivado) {
                $vehiculoId = $this->restaurarVehiculo($vehiculoArchivado->datos_vehiculo ?? []);
                $pivote = $this->filtrarColumnas('hecho_vehiculo', $vehiculoArchivado->datos_pivote ?? []);
                $pivote['hecho_id'] = $hechoId;
                $pivote['vehiculo_id'] = $vehiculoId;
                DB::table('hecho_vehiculo')->insertOrIgnore($pivote);

                $this->restaurarConductores($vehiculoId, $vehiculoArchivado->conductores ?? []);
                $this->restaurarServicios($vehiculoId, $vehiculoArchivado->servicios ?? []);
            }

            foreach ($archivo->lesionados ?? [] as $lesionado) {
                $datos = $this->filtrarColumnas('lesionados', $lesionado);
                $datos['hecho_id'] = $hechoId;
                if (isset($datos['id']) && DB::table('lesionados')->where('id', $datos['id'])->exists()) {
                    unset($datos['id']);
                }
                $this->insertarRegistro('lesionados', $datos);
            }

            foreach ($archivo->croquis ?? [] as $croquis) {
                $datos = $this->filtrarColumnas('croquis', $croquis);
                $datos['hecho_id'] = $hechoId;
                if (isset($datos['id']) && DB::table('croquis')->where('id', $datos['id'])->exists()) {
                    unset($datos['id']);
                }
                $this->insertarRegistro('croquis', $datos);
            }

            if ($archivo->dictamen_id && Schema::hasTable('dictamens')) {
                DB::table('dictamens')
                    ->where('id', $archivo->dictamen_id)
                    ->whereNull('hecho_id')
                    ->update(['hecho_id' => $hechoId]);
            }

            $archivo->update([
                'hecho_id_restaurado' => $hechoId,
                'restaurado_por' => $usuarioId,
                'restaurado_at' => now(),
            ]);

            return Hechos::query()->findOrFail($hechoId);
        });
    }

    private function restaurarVehiculo(array $datos): int
    {
        $datos = $this->filtrarColumnas('vehiculos', $datos);
        $idOriginal = isset($datos['id']) ? (int) $datos['id'] : 0;

        if ($idOriginal && DB::table('vehiculos')->where('id', $idOriginal)->exists()) {
            return $idOriginal;
        }

        if (!empty($datos['client_uuid'])) {
            $existente = DB::table('vehiculos')->where('client_uuid', $datos['client_uuid'])->value('id');
            if ($existente) {
                return (int) $existente;
            }
        }

        return $this->insertarRegistro('vehiculos', $datos);
    }

    private function restaurarConductores(int $vehiculoId, array $conductores): void
    {
        foreach ($conductores as $conductorArchivado) {
            $datos = $this->filtrarColumnas('conductores', Arr::get($conductorArchivado, 'datos', []));
            $conductorId = isset($datos['id']) ? (int) $datos['id'] : 0;

            if (!$conductorId || !DB::table('conductores')->where('id', $conductorId)->exists()) {
                if (!empty($datos['client_uuid'])) {
                    $conductorId = (int) (DB::table('conductores')->where('client_uuid', $datos['client_uuid'])->value('id') ?? 0);
                }
                if (!$conductorId) {
                    $conductorId = $this->insertarRegistro('conductores', $datos);
                }
            }

            $pivote = $this->filtrarColumnas('vehiculo_conductor', Arr::get($conductorArchivado, 'pivote', []));
            $pivote['vehiculo_id'] = $vehiculoId;
            $pivote['conductor_id'] = $conductorId;
            DB::table('vehiculo_conductor')->insertOrIgnore($pivote);
        }
    }

    private function restaurarServicios(int $vehiculoId, array $servicios): void
    {
        foreach ($servicios as $servicio) {
            $datos = $this->filtrarColumnas('servicios', $servicio);
            $datos['vehiculo_id'] = $vehiculoId;
            if (isset($datos['id']) && DB::table('servicios')->where('id', $datos['id'])->exists()) {
                continue;
            }
            $this->insertarRegistro('servicios', $datos);
        }
    }

    private function filtrarColumnas(string $tabla, array $datos): array
    {
        if (!Schema::hasTable($tabla)) {
            return [];
        }

        return Arr::only($datos, Schema::getColumnListing($tabla));
    }

    private function insertarRegistro(string $tabla, array $datos): int
    {
        if (!$datos) {
            throw new RuntimeException("No hay datos para restaurar en {$tabla}.");
        }

        if (isset($datos['id'])) {
            DB::table($tabla)->insert($datos);
            return (int) $datos['id'];
        }

        return (int) DB::table($tabla)->insertGetId($datos);
    }
}
