<?php

namespace App\Http\Controllers;

use App\Models\ArchivoArea;
use App\Models\ArchivoLibro;
use App\Services\ArchivoUbicacionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoMovimientoController extends Controller
{
    public function index(ArchivoLibro $libro)
    {
        $movimientos = $libro->movimientos()
            ->with(['usuario', 'bloqueOrigen.anaquel.area', 'bloqueDestino.anaquel.area', 'areaOrigen', 'areaDestino'])
            ->orderByDesc('fecha_movimiento')->orderByDesc('id')->paginate(50);
        return view('admin.settings.archivo_siniestros.movimientos.index', compact('libro', 'movimientos'));
    }

    public function confirmarUbicacion(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_qr_uuid' => ['required', 'uuid'],
            'posicion' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);
        $ubicaciones->confirmar($libro, $datos['bloque_qr_uuid'], $datos['posicion'] ?? null, (int) $request->user()->id);
        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Ubicación física confirmada.');
    }

    public function retirar(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'area_destino_id' => ['required', 'integer', Rule::exists('archivo_areas', 'id')->where('activo', true)],
            'motivo' => ['required', 'string', 'max:1000'],
        ]);
        $ubicaciones->retirar($libro, (int) $datos['area_destino_id'], $datos['motivo'], (int) $request->user()->id);
        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Retiro registrado.');
    }

    public function devolver(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_qr_uuid' => ['required', 'uuid'],
            'posicion' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);
        $ubicaciones->devolver($libro, $datos['bloque_qr_uuid'], $datos['posicion'] ?? null, (int) $request->user()->id);
        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Devolución registrada.');
    }

    public function reubicar(Request $request, ArchivoLibro $libro, ArchivoUbicacionService $ubicaciones)
    {
        $datos = $request->validate([
            'bloque_destino_id' => ['required', 'integer', Rule::exists('archivo_bloques', 'id')->where('activo', true)],
            'bloque_qr_uuid' => ['required', 'uuid'],
            'posicion' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);
        $ubicaciones->reubicar($libro, (int) $datos['bloque_destino_id'], $datos['bloque_qr_uuid'], $datos['posicion'] ?? null, (int) $request->user()->id);
        return redirect()->route('archivo_siniestros.libros.show', $libro)->with('success', 'Reubicación registrada.');
    }
}
