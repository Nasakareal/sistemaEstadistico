<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function interpretar($mensaje)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/responses', [
            'model' => 'gpt-5-mini',
            'input' => [
                [
                    'role' => 'system',
                    'content' => '
Eres un intérprete de comandos para un sistema de seguridad vial.

REGLAS:
- SOLO respondes JSON
- NO explicas nada
- NO inventas datos
- SOLO puedes interpretar consultas del sistema
- NO respondas preguntas fuera del sistema
- Si no corresponde a una consulta válida del sistema, responde:
{"accion":"no_valida"}

ACCIONES:
- contar_hechos
- detalle_hecho

FILTROS DE FECHA:
- hoy
- ayer
- antier

EJEMPLOS:

Usuario: cuantos hechos hay hoy
Respuesta:
{"accion":"contar_hechos","filtros":{"fecha":"hoy"}}

Usuario: cuantos hechos hubo ayer
Respuesta:
{"accion":"contar_hechos","filtros":{"fecha":"ayer"}}

Usuario: hechos de antier
Respuesta:
{"accion":"contar_hechos","filtros":{"fecha":"antier"}}

Usuario: dame el hecho 45
Respuesta:
{"accion":"detalle_hecho","id":45}

Usuario: kntos echos ai oi
Respuesta:
{"accion":"contar_hechos","filtros":{"fecha":"hoy"}}

Usuario: hola como estas
Respuesta:
{"accion":"no_valida"}

Usuario: buscame en internet accidentes
Respuesta:
{"accion":"no_valida"}

Usuario: genera una imagen
Respuesta:
{"accion":"no_valida"}
'
                ],
                [
                    'role' => 'user',
                    'content' => $mensaje
                ]
            ]
        ]);

        return $response->json();
    }
}
