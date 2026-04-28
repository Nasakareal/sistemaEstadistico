<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaExamen;
use App\Models\ConstanciaExamenRespuesta;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaPregunta;
use App\Models\ConstanciaRespuesta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConstanciaExamenPublicoController extends Controller
{
    public function iniciar($token)
    {
        $constancia = ConstanciaManejo::where('acceso_examen_token', $token)->firstOrFail();

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Esta constancia no está disponible para examen.',
            ]);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El acceso temporal al examen ya expiró.',
            ]);
        }

        if ($constancia->examen && $constancia->examen->resultado === 'APROBADO') {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'Este examen ya fue aprobado. Espere la activación del perito examinador.',
            ]);
        }

        $preguntas = ConstanciaPregunta::with('respuestas')
            ->where('activo', true)
            ->where(function ($query) use ($constancia) {
                $query->where('tipo_licencia', $constancia->tipo_licencia)
                    ->orWhere('tipo_licencia', 'GENERAL');
            })
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return view('constancias_manejo.examen.iniciar', compact('constancia', 'preguntas', 'token'));
    }

    public function guardar(Request $request, $token)
    {
        $constancia = ConstanciaManejo::where('acceso_examen_token', $token)->firstOrFail();

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.examen.iniciar', $token);
        }

        if (!$constancia->acceso_examen_expira || Carbon::now('America/Mexico_City')->greaterThan($constancia->acceso_examen_expira)) {
            return view('constancias_manejo.examen.bloqueado', [
                'mensaje' => 'El acceso temporal al examen ya expiró.',
            ]);
        }

        $request->validate([
            'respuestas' => ['required', 'array'],
            'respuestas.*' => ['required', 'integer', 'exists:constancia_respuestas,id'],
        ]);

        $resultado = DB::transaction(function () use ($request, $constancia) {
            $respuestasIds = array_values($request->respuestas);
            $respuestas = ConstanciaRespuesta::with('pregunta')
                ->whereIn('id', $respuestasIds)
                ->get();

            $total = $respuestas->count();
            $aciertos = $respuestas->where('es_correcta', true)->count();
            $errores = $total - $aciertos;
            $calificacion = $total > 0 ? round(($aciertos / $total) * 100, 2) : 0;
            $resultado = $calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

            $examen = ConstanciaExamen::updateOrCreate(
                [
                    'constancia_id' => $constancia->id,
                ],
                [
                    'modalidad' => 'LINEA',
                    'calificacion' => $calificacion,
                    'total_preguntas' => $total,
                    'aciertos' => $aciertos,
                    'errores' => $errores,
                    'resultado' => $resultado,
                    'capturado_por' => null,
                    'fecha_examen' => Carbon::now('America/Mexico_City'),
                    'observaciones' => null,
                ]
            );

            ConstanciaExamenRespuesta::where('constancia_examen_id', $examen->id)->delete();

            foreach ($respuestas as $respuesta) {
                ConstanciaExamenRespuesta::create([
                    'constancia_examen_id' => $examen->id,
                    'pregunta_id' => $respuesta->pregunta_id,
                    'respuesta_id' => $respuesta->id,
                    'es_correcta' => $respuesta->es_correcta,
                ]);
            }

            return [
                'examen' => $examen,
                'resultado' => $resultado,
            ];
        });

        return view('constancias_manejo.examen.resultado', [
            'constancia' => $constancia,
            'examen' => $resultado['examen'],
        ]);
    }
}
