<?php

namespace App\Services;

use App\Models\ArchivoBloque;
use App\Models\ArchivoLibro;
use App\Models\ArchivoMovimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchivoUbicacionService
{
    public function asignarDestino(ArchivoLibro $libro, int $bloqueId, ?int $posicion, int $usuarioId): ArchivoLibro
    {
        return DB::transaction(function () use ($libro, $bloqueId, $posicion, $usuarioId) {
            $libro = ArchivoLibro::lockForUpdate()->findOrFail($libro->id);
            $bloque = $this->bloqueActivo($bloqueId);
            $this->reservarDestino($libro, $bloque, $posicion);

            ArchivoMovimiento::create([
                'libro_id' => $libro->id,
                'tipo' => 'asignacion_destino',
                'origen_area_id' => $libro->area_actual_id,
                'origen_bloque_id' => $libro->bloque_actual_id,
                'origen_posicion' => $libro->posicion_actual,
                'destino_area_id' => $bloque->anaquel->area_id,
                'destino_bloque_id' => $bloque->id,
                'destino_posicion' => $libro->posicion_destino,
                'usuario_id' => $usuarioId,
                'motivo' => 'Asignación de ubicación prevista',
            ]);

            return $libro->fresh(['bloqueDestino.anaquel.area']);
        }, 3);
    }

    public function confirmar(ArchivoLibro $libro, string $qrUuid, ?int $posicion, int $usuarioId): ArchivoLibro
    {
        return DB::transaction(function () use ($libro, $qrUuid, $posicion, $usuarioId) {
            $libro = ArchivoLibro::lockForUpdate()->findOrFail($libro->id);
            if (!$libro->bloque_destino_id) {
                $this->fallar('Primero asigna un bloque de destino al libro.');
            }
            $bloque = $this->bloqueActivo((int) $libro->bloque_destino_id);
            $this->comprobarQr($bloque, $qrUuid);
            return $this->trasladar($libro, $bloque, $posicion, 'confirmacion', $usuarioId);
        }, 3);
    }

    public function retirar(ArchivoLibro $libro, int $areaId, string $motivo, int $usuarioId): ArchivoLibro
    {
        return DB::transaction(function () use ($libro, $areaId, $motivo, $usuarioId) {
            $libro = ArchivoLibro::lockForUpdate()->findOrFail($libro->id);
            $area = DB::table('archivo_areas')->where('id', $areaId)->where('activo', true)->first();
            if (!$area) {
                $this->fallar('El área seleccionada no está disponible.');
            }
            if (!$libro->bloque_actual_id) {
                $this->fallar('El libro no se encuentra actualmente en un bloque.');
            }

            $bloqueOrigen = ArchivoBloque::whereKey($libro->bloque_actual_id)->lockForUpdate()->firstOrFail();
            $origen = [
                'area' => $libro->area_actual_id,
                'bloque' => $libro->bloque_actual_id,
                'posicion' => $libro->posicion_actual,
            ];
            $restantes = $this->idsOrdenados((int) $bloqueOrigen->id, (int) $libro->id);
            $this->vaciarPosiciones(array_merge($restantes, [$libro->id]));

            $libro->area_actual_id = $areaId;
            $libro->bloque_actual_id = null;
            $libro->posicion_actual = null;
            $libro->estado_fisico = 'en_consulta';
            $libro->save();
            $this->numerar($restantes);

            $this->registrarMovimiento($libro, 'retiro', $origen, [
                'area' => $areaId,
                'bloque' => null,
                'posicion' => null,
            ], $usuarioId, $motivo);

            return $libro->fresh(['areaActual', 'bloqueDestino.anaquel.area']);
        }, 3);
    }

    public function devolver(ArchivoLibro $libro, string $qrUuid, ?int $posicion, int $usuarioId): ArchivoLibro
    {
        return DB::transaction(function () use ($libro, $qrUuid, $posicion, $usuarioId) {
            $libro = ArchivoLibro::lockForUpdate()->findOrFail($libro->id);
            if ($libro->bloque_actual_id) {
                $this->fallar('El libro ya aparece almacenado en un bloque.');
            }
            if (!$libro->bloque_destino_id) {
                $this->fallar('Este libro no tiene un bloque de destino asignado.');
            }
            $bloque = $this->bloqueActivo((int) $libro->bloque_destino_id);
            $this->comprobarQr($bloque, $qrUuid);
            return $this->trasladar($libro, $bloque, $posicion, 'devolucion', $usuarioId);
        }, 3);
    }

    public function reubicar(ArchivoLibro $libro, int $bloqueId, string $qrUuid, ?int $posicion, int $usuarioId): ArchivoLibro
    {
        return DB::transaction(function () use ($libro, $bloqueId, $qrUuid, $posicion, $usuarioId) {
            $libro = ArchivoLibro::lockForUpdate()->findOrFail($libro->id);
            $bloque = $this->bloqueActivo($bloqueId);
            $this->comprobarQr($bloque, $qrUuid);
            $this->reservarDestino($libro, $bloque, null);
            return $this->trasladar($libro, $bloque, $posicion, 'reubicacion', $usuarioId);
        }, 3);
    }

    private function trasladar(ArchivoLibro $libro, ArchivoBloque $destino, ?int $posicion, string $tipo, int $usuarioId): ArchivoLibro
    {
        $origen = [
            'area' => $libro->area_actual_id,
            'bloque' => $libro->bloque_actual_id,
            'posicion' => $libro->posicion_actual,
        ];
        $origenId = $libro->bloque_actual_id ? (int) $libro->bloque_actual_id : null;
        $destinoId = (int) $destino->id;

        if ($origenId && $origenId !== $destinoId) {
            ArchivoBloque::whereKey($origenId)->lockForUpdate()->firstOrFail();
        }
        if ($origenId !== $destinoId) {
            $this->comprobarCupo($destino, $libro);
        }

        $origenRestantes = $origenId && $origenId !== $destinoId
            ? $this->idsOrdenados($origenId, (int) $libro->id)
            : [];
        $destinoRestantes = $this->idsOrdenados($destinoId, (int) $libro->id);
        $maxPosicion = count($destinoRestantes) + 1;
        $nuevaPosicion = $posicion ?? ($destino->orden_apilado === 'abajo_arriba' ? $maxPosicion : 1);

        if ($nuevaPosicion < 1 || $nuevaPosicion > $maxPosicion) {
            $this->fallar("La posición debe estar entre 1 y {$maxPosicion}.");
        }
        if ($maxPosicion > $destino->capacidad_libros) {
            $this->fallar('El bloque de destino superaría su capacidad máxima.');
        }

        $this->vaciarPosiciones(array_unique(array_merge($origenRestantes, $destinoRestantes, [$libro->id])));
        $libro->area_actual_id = $destino->anaquel->area_id;
        $libro->bloque_actual_id = $destinoId;
        $libro->posicion_actual = null;
        $libro->estado_fisico = 'almacenado';
        $libro->save();

        array_splice($destinoRestantes, $nuevaPosicion - 1, 0, [$libro->id]);
        $this->numerar($origenRestantes);
        $this->numerar($destinoRestantes);

        $this->registrarMovimiento($libro, $tipo, $origen, [
            'area' => $destino->anaquel->area_id,
            'bloque' => $destinoId,
            'posicion' => $nuevaPosicion,
        ], $usuarioId, $tipo);

        return $libro->fresh(['areaActual', 'bloqueActual.anaquel.area', 'bloqueDestino.anaquel.area']);
    }

    private function reservarDestino(ArchivoLibro $libro, ArchivoBloque $bloque, ?int $posicion): void
    {
        $this->comprobarCupo($bloque, $libro);
        $ocupadas = ArchivoLibro::where('bloque_destino_id', $bloque->id)
            ->where('id', '<>', $libro->id)
            ->whereNotNull('posicion_destino')
            ->pluck('posicion_destino')
            ->map(fn ($numero) => (int) $numero)
            ->all();

        if ($posicion === null) {
            for ($i = 1; $i <= $bloque->capacidad_libros; $i++) {
                if (!in_array($i, $ocupadas, true)) {
                    $posicion = $i;
                    break;
                }
            }
        }
        if (!$posicion || $posicion < 1 || $posicion > $bloque->capacidad_libros || in_array($posicion, $ocupadas, true)) {
            $this->fallar('No hay una posición de destino disponible en ese bloque.');
        }

        $libro->bloque_destino_id = $bloque->id;
        $libro->posicion_destino = $posicion;
        $libro->save();
    }

    private function comprobarCupo(ArchivoBloque $bloque, ArchivoLibro $libro): void
    {
        $ocupacion = ArchivoLibro::where('id', '<>', $libro->id)
            ->where(function ($query) use ($bloque) {
                $query->where('bloque_actual_id', $bloque->id)
                    ->orWhere('bloque_destino_id', $bloque->id);
            })->count();
        if ($ocupacion >= $bloque->capacidad_libros) {
            $this->fallar('El bloque ya está lleno, contando los libros almacenados y reservados.');
        }
    }

    private function bloqueActivo(int $bloqueId): ArchivoBloque
    {
        $bloque = ArchivoBloque::with('anaquel.area')->whereKey($bloqueId)->lockForUpdate()->firstOrFail();
        if (!$bloque->activo || !$bloque->anaquel->activo || !$bloque->anaquel->area->activo) {
            $this->fallar('El bloque, anaquel o área de destino está desactivado.');
        }
        return $bloque;
    }

    private function comprobarQr(ArchivoBloque $bloque, string $uuid): void
    {
        if (!$bloque->qr_uuid || !hash_equals((string) $bloque->qr_uuid, $uuid)) {
            $this->fallar('El código QR no corresponde al bloque de destino.');
        }
    }

    private function idsOrdenados(int $bloqueId, int $excluirLibroId): array
    {
        return ArchivoLibro::where('bloque_actual_id', $bloqueId)
            ->where('id', '<>', $excluirLibroId)
            ->orderBy('posicion_actual')
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->all();
    }

    private function vaciarPosiciones(array $ids): void
    {
        if ($ids) {
            ArchivoLibro::whereIn('id', $ids)->update(['posicion_actual' => null]);
        }
    }

    private function numerar(array $ids): void
    {
        foreach (array_values($ids) as $indice => $id) {
            ArchivoLibro::whereKey($id)->update(['posicion_actual' => $indice + 1]);
        }
    }

    private function registrarMovimiento(ArchivoLibro $libro, string $tipo, array $origen, array $destino, int $usuarioId, string $motivo): void
    {
        ArchivoMovimiento::create([
            'libro_id' => $libro->id,
            'tipo' => $tipo,
            'origen_area_id' => $origen['area'],
            'origen_bloque_id' => $origen['bloque'],
            'origen_posicion' => $origen['posicion'],
            'destino_area_id' => $destino['area'],
            'destino_bloque_id' => $destino['bloque'],
            'destino_posicion' => $destino['posicion'],
            'usuario_id' => $usuarioId,
            'motivo' => $motivo,
        ]);
    }

    private function fallar(string $mensaje): void
    {
        throw ValidationException::withMessages(['ubicacion' => $mensaje]);
    }
}
