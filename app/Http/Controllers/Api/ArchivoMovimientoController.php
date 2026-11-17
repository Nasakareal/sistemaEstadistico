<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArchivoLibro;
use App\Services\ArchivoUbicacionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoMovimientoController extends Controller
{
    public function index(Request $request, ArchivoLibro $libro)
    {
        $filtros = $request->validate(['per_page' => ['nullable', 'integer', 'between:1,100']]);
        $movimientos = $libro->movimientos()
            ->with('usuario:id,name')
            ->orderByDesc('fecha_movimiento')->orderByDesc('id')
            ->paginate((int) ($filtros['per_page'] ?? 50));

        $movimientos->getCollection()->transform(fn ($m) => array_merge($m->only([
            'id', 'libro_id', 'tipo', 'origen_area_id', 'origen_bloque_id',
            'origen_posicion', 'destino_area_id', 'destino_bloque_id',
            'destino_posicion', 'motivo', 'observaciones', 'fecha_movimiento',
        ]), [
            'usuario' => $m->usuario?->only(['id', 'name']),
        ]));

        return \App\Support\ApiPaginationResponse::make($movimientos);
    }

    public function confirmarUbicacion(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_qr_uuid' => ['required', 'uuid'],
            'posicion' => ['nullable', 'integer', 'between:1,65535'],
        ]);
        $libro = $ubicaciones->confirmar($libro, $datos['bloque_qr_uuid'], $datos['posicion'] ?? null, (int) $request->user()->id);
        return $this->resultado('Ubicación física confirmada.', $libro);
    }

    public function retirar(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'area_destino_id' => ['required', 'integer', Rule::exists('archivo_areas', 'id')->where('activo', true)],
            'motivo' => ['required', 'string', 'max:1000'],
        ]);
        $libro = $ubicaciones->retirar($libro, (int) $datos['area_destino_id'], $datos['motivo'], (int) $request->user()->id);
        return $this->resultado('Retiro registrado.', $libro);
    }

    public function devolver(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_qr_uuid' => ['required', 'uuid'],
            'posicion' => ['nullable', 'integer', 'between:1,65535'],
        ]);
        $libro = $ubicaciones->devolver($libro, $datos['bloque_qr_uuid'], $datos['posicion'] ?? null, (int) $request->user()->id);
        return $this->resultado('Devolución registrada.', $libro);
    }

    public function reubicar(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_destino_id' => ['required', 'integer', Rule::exists('archivo_bloques', 'id')->where('activo', true)],
            'bloque_qr_uuid' => ['required', 'uuid'],
            'posicion' => ['nullable', 'integer', 'between:1,65535'],
        ]);
        $libro = $ubicaciones->reubicar($libro, (int) $datos['bloque_destino_id'], $datos['bloque_qr_uuid'], $datos['posicion'] ?? null, (int) $request->user()->id);
        return $this->resultado('Reubicación registrada.', $libro);
    }

    private function resultado(string $mensaje, ArchivoLibro $libro)
    {
        return response()->json([
            'message' => $mensaje,
            'data' => $libro->only([
                'id', 'codigo', 'estado_fisico', 'area_actual_id', 'bloque_actual_id',
                'posicion_actual', 'bloque_destino_id', 'posicion_destino',
            ]),
        ]);
    }
}
