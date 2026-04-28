<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaManejo;
use Carbon\Carbon;

class ConstanciaValidacionController extends Controller
{
    public function validar($token)
    {
        $constancia = ConstanciaManejo::with(['modulo', 'examen', 'peritoActivador'])
            ->where('qr_token', $token)
            ->firstOrFail();

        if ($constancia->estatus === 'ACTIVA' && $constancia->fecha_expiracion && Carbon::now('America/Mexico_City')->greaterThan($constancia->fecha_expiracion)) {
            $constancia->update([
                'estatus' => 'EXPIRADA',
            ]);
        }

        $mensaje = null;
        $tipoMensaje = 'info';

        if (auth()->check() && $constancia->estatus === 'IMPRESA_INACTIVA') {
            if (!auth()->user()->can('editar modulo examenes')) {
                $mensaje = 'Constancia pendiente de activacion. Tu usuario no tiene permiso para activarla.';
                $tipoMensaje = 'warning';
            } elseif (!$constancia->nombre_solicitante || !$constancia->tipo_licencia || !$constancia->tipo_examen) {
                $mensaje = 'No se activó: faltan datos del solicitante, tipo de licencia o tipo de examen.';
                $tipoMensaje = 'warning';
            } elseif (!$constancia->examen || $constancia->examen->resultado !== 'APROBADO') {
                $mensaje = 'No se activó: la constancia todavía no tiene examen aprobado.';
                $tipoMensaje = 'warning';
            } else {
                $ahora = Carbon::now('America/Mexico_City');

                $constancia->update([
                    'estatus' => 'ACTIVA',
                    'perito_activador_id' => auth()->id(),
                    'fecha_activacion' => $ahora,
                    'fecha_expiracion' => $ahora->copy()->addDays(10),
                    'acceso_examen_token' => null,
                    'acceso_examen_expira' => null,
                ]);

                ConstanciaActivacion::create([
                    'constancia_id' => $constancia->id,
                    'user_id' => auth()->id(),
                    'accion' => 'ACTIVADA',
                    'fecha' => $ahora,
                    'observaciones' => 'Activada por escaneo de QR.',
                ]);

                $mensaje = 'Constancia activada. La vigencia de 10 días ya comenzó.';
                $tipoMensaje = 'success';
            }
        }

        ConstanciaActivacion::create([
            'constancia_id' => $constancia->id,
            'user_id' => auth()->id(),
            'accion' => 'VALIDADA_QR',
            'fecha' => Carbon::now('America/Mexico_City'),
            'observaciones' => null,
        ]);

        $constancia->refresh();

        return view('constancias_manejo.validar', compact('constancia', 'mensaje', 'tipoMensaje'));
    }
}
