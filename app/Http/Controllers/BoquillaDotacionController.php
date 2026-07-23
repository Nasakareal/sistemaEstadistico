<?php

namespace App\Http\Controllers;

use App\Models\BoquillaDotacion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BoquillaDotacionController extends Controller
{
    public function index(Request $request)
    {
        $mes = $this->mesSeleccionado($request->input('mes'));
        $inicioMes = $mes->copy()->startOfMonth();
        $finMes = $mes->copy()->endOfMonth();

        $dotaciones = BoquillaDotacion::query()
            ->with('creador:id,name')
            ->whereBetween('fecha_recepcion', [
                $inicioMes->toDateString(),
                $finMes->toDateString(),
            ])
            ->orderByDesc('fecha_recepcion')
            ->orderByDesc('id')
            ->get();

        $totalRecibidasMes = (int) $dotaciones->sum('cantidad');

        return view('admin.settings.boquillas.index', [
            'dotaciones' => $dotaciones,
            'mes' => $inicioMes->format('Y-m'),
            'mesAnterior' => $inicioMes->copy()->subMonth()->format('Y-m'),
            'mesSiguiente' => $inicioMes->copy()->addMonth()->format('Y-m'),
            'tituloMes' => ucfirst($inicioMes->locale('es')->translatedFormat('F Y')),
            'totalRecibidasMes' => $totalRecibidasMes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        BoquillaDotacion::create($data);

        return redirect()
            ->route('settings.boquillas.index', ['mes' => Carbon::parse($data['fecha_recepcion'])->format('Y-m')])
            ->with('success', 'La dotación de boquillas se registró correctamente.');
    }

    public function update(Request $request, BoquillaDotacion $dotacion)
    {
        $data = $this->validar($request);
        $data['updated_by'] = $request->user()->id;

        $dotacion->update($data);

        return redirect()
            ->route('settings.boquillas.index', ['mes' => Carbon::parse($data['fecha_recepcion'])->format('Y-m')])
            ->with('success', 'La dotación de boquillas se actualizó correctamente.');
    }

    public function destroy(Request $request, BoquillaDotacion $dotacion)
    {
        $mes = $dotacion->fecha_recepcion->format('Y-m');
        $dotacion->updated_by = $request->user()->id;
        $dotacion->save();
        $dotacion->delete();

        return redirect()
            ->route('settings.boquillas.index', ['mes' => $mes])
            ->with('success', 'La dotación se eliminó del cálculo mensual.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'fecha_recepcion' => ['required', 'date'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'fecha_recepcion.required' => 'Indica la fecha en que se recibieron las boquillas.',
            'fecha_recepcion.date' => 'La fecha de recepción no es válida.',
            'cantidad.required' => 'Indica cuántas boquillas se recibieron.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'cantidad.max' => 'La cantidad capturada es demasiado grande.',
            'observaciones.max' => 'Las observaciones no pueden exceder 500 caracteres.',
        ]);
    }

    private function mesSeleccionado(?string $mes): Carbon
    {
        if ($mes && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return Carbon::createFromFormat('Y-m-d', $mes . '-01')->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
