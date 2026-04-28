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

        ConstanciaActivacion::create([
            'constancia_id' => $constancia->id,
            'user_id' => auth()->id(),
            'accion' => 'VALIDADA_QR',
            'fecha' => Carbon::now('America/Mexico_City'),
            'observaciones' => null,
        ]);

        $constancia->refresh();

        return view('constancias_manejo.validar', compact('constancia'));
    }
}
