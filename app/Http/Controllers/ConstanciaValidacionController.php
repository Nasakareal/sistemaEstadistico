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
            } elseif (!$constancia->tieneDatosMinimosActivacion()) {
                $mensaje = 'No se activó: faltan datos del solicitante, sexo o tipo de licencia.';
                $tipoMensaje = 'warning';
            } elseif (!$constancia->tieneExamenAprobado() && !$constancia->puedeActivarDirectamente()) {
                $mensaje = 'No se activó: la constancia todavía no tiene examen aprobado.';
                $tipoMensaje = 'warning';
            } else {
                $observaciones = $constancia->puedeActivarDirectamente()
                    ? 'Activada por escaneo de QR sin examen asociado.'
                    : 'Activada por escaneo de QR.';
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
                    'observaciones' => $observaciones,
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
