<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function interpretar($mensaje)
    {
        $apiKey = (string) config('services.openai.key');

        if ($apiKey === '') {
            return $this->respuestaNoValida();
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.model', 'gpt-5-mini'),
                'input' => [
                    [
                        'role' => 'system',
                        'content' => $this->promptSistema(),
                    ],
                    [
                        'role' => 'user',
                        'content' => (string) $mensaje,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('OpenAI interpretar error', ['error' => $e->getMessage()]);

            return $this->respuestaNoValida();
        }

        if (!$response->successful()) {
            Log::warning('OpenAI interpretar respuesta no exitosa', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->respuestaNoValida();
        }

        $data = $response->json();
        $text = $data['output_text']
            ?? $data['output'][0]['content'][0]['text']
            ?? '{"accion":"no_valida"}';

        $decoded = json_decode($this->limpiarJson((string) $text), true);

        if (!is_array($decoded)) {
            return $this->respuestaNoValida();
        }

        return $this->normalizarRespuesta($decoded);
    }

    protected function promptSistema(): string
    {
        $hoy = now();
        $ayer = now()->subDay();
        $antier = now()->subDays(2);
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = now()->subMonthNoOverflow()->endOfMonth();
        $anio = (int) now()->year;
        $sinonimosOperativos = $this->sinonimosOperativosPrompt();
        $tiposOperativos = $this->tiposOperativosPrompt();

        return <<<PROMPT
Eres un intérprete de comandos para un sistema de seguridad vial.

REGLAS:
- SOLO respondes JSON válido.
- NO explicas nada.
- NO uses markdown.
- NO inventas datos.
- NO redactas resultados finales.
- Si no corresponde a una consulta válida del sistema, responde exactamente {"accion":"no_valida"}.

FECHA ACTUAL:
{$hoy->toDateString()}

ACCIONES VÁLIDAS:
- contar_hechos
- detalle_hecho
- lista_hechos
- estadistica_hechos
- resumen_hechos
- personal_armado
- personal_activo
- actividades
- operativos
- puestas_disposicion

ESTRUCTURA OBLIGATORIA:
{
  "accion": "nombre_accion",
  "unidad_id": null,
  "id": null,
  "filtros": {
    "fecha": null,
    "fecha_inicio": null,
    "fecha_fin": null,
    "hora_inicio": null,
    "hora_fin": null,
    "tipo_hecho": null,
    "situacion": null,
    "tipo_operativo": null,
    "tipo_puesta": null,
    "estatus": null,
    "municipio": null,
    "delegacion_id": null
  }
}

REGLAS DE ACCIÓN:
- Si pide cuántos hechos hay, usa contar_hechos.
- Si pide un hecho específico, usa detalle_hecho e incluye id.
- Si pide lista de hechos, usa lista_hechos.
- Si pide estadística, resumen o desglose de hechos, usa estadistica_hechos o resumen_hechos.
- Si pide personal armado, relación de armamento o lista de elementos armados, usa personal_armado.
- Si pide personal activo, usa personal_activo.
- Si pide actividades, apoyos, labores o proximidad social, usa actividades.
- Si pide operativos, dispositivos o cualquiera de los tipos del catálogo de carreteras, usa operativos.
- Si pide puestas a disposición, usa puestas_disposicion.

REGLAS DE UNIDAD:
- Si menciona siniestros, unidad_id = 1.
- Si menciona delegaciones, unidad_id = 2.
- Si menciona coordinación, coordinacion o seguridad vial, unidad_id = 3.
- Si menciona carreteras o protección a carreteras, unidad_id = 4.
- Si menciona vialidades, vialidades urbanas o protección a vialidades urbanas, unidad_id = 5.
- Si dice mi unidad, mis hechos, mi personal, mis actividades o no menciona unidad, unidad_id = null.
- Nunca inventes unidad_id si no está claro.

REGLAS DE FECHAS:
- Hoy = {$hoy->toDateString()}.
- Ayer = {$ayer->toDateString()}.
- Antier = {$antier->toDateString()}.
- Este mes = {$inicioMes->toDateString()} al {$finMes->toDateString()}.
- Mes anterior = {$inicioMesAnterior->toDateString()} al {$finMesAnterior->toDateString()}.
- Este año = {$anio}-01-01 al {$anio}-12-31.
- Si pide fecha exacta, usa fecha.
- Si pide rango o mes completo, usa fecha_inicio y fecha_fin.
- Si pide horas, usa hora_inicio y hora_fin en formato HH:MM:SS.
- Si no especifica fecha, deja fecha, fecha_inicio y fecha_fin en null.
- Si menciona un mes sin año, usa {$anio}.

REGLAS DE FILTROS:
- Si menciona tipo de hecho, llena tipo_hecho en mayúsculas.
- Si menciona resuelto, pendiente, turnado o reporte, llena situacion en mayúsculas.
- Si menciona tipo de operativo, llena tipo_operativo con el nombre en mayúsculas.
- Si menciona tipo de puesta, llena tipo_puesta.
- Si menciona estatus de puesta, llena estatus en mayúsculas.
- Si menciona municipio, llena municipio.
- Si menciona delegación por id numérico, llena delegacion_id.

SINÓNIMOS DE OPERATIVOS DE CARRETERAS:
{$sinonimosOperativos}

TIPOS DE OPERATIVO VÁLIDOS EN CARRETERAS:
{$tiposOperativos}

EJEMPLOS:
Usuario: cuantos hechos hay hoy
Respuesta: {"accion":"contar_hechos","unidad_id":null,"id":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: dame el hecho 59564
Respuesta: {"accion":"detalle_hecho","unidad_id":null,"id":59564,"filtros":{"fecha":null,"fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: dame mi personal armado
Respuesta: {"accion":"personal_armado","unidad_id":null,"id":null,"filtros":{"fecha":null,"fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantas actividades hubo hoy
Respuesta: {"accion":"actividades","unidad_id":null,"id":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantos operativos hubo hoy en carreteras
Respuesta: {"accion":"operativos","unidad_id":4,"id":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantas puestas a disposición hubo hoy
Respuesta: {"accion":"puestas_disposicion","unidad_id":null,"id":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: hola como estas
Respuesta: {"accion":"no_valida"}
PROMPT;
    }

    protected function limpiarJson(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        return trim((string) $text);
    }

    protected function sinonimosOperativosPrompt(): string
    {
        $terminos = [
            'operativo',
            'operativos',
            'dispositivo',
            'dispositivos',
        ];

        foreach ($this->guardianesDispositivos() as $dispositivo) {
            $terminos[] = (string) ($dispositivo['nombre'] ?? '');

            foreach ((array) ($dispositivo['aliases'] ?? []) as $alias) {
                $terminos[] = (string) $alias;
            }
        }

        $terminos = array_values(array_unique(array_filter(array_map('trim', $terminos))));

        return '- ' . implode("\n- ", $terminos);
    }

    protected function tiposOperativosPrompt(): string
    {
        $tipos = [];

        foreach ($this->guardianesDispositivos() as $dispositivo) {
            $nombre = trim((string) ($dispositivo['nombre'] ?? ''));

            if ($nombre !== '') {
                $tipos[] = $nombre;
            }
        }

        return '- ' . implode("\n- ", $tipos);
    }

    protected function guardianesDispositivos(): array
    {
        $dispositivos = config('guardianes_camino.dispositivos', []);

        return is_array($dispositivos) ? $dispositivos : [];
    }

    protected function normalizarRespuesta(array $decoded): array
    {
        $accion = (string) ($decoded['accion'] ?? 'no_valida');

        $accionesValidas = [
            'contar_hechos',
            'detalle_hecho',
            'lista_hechos',
            'estadistica_hechos',
            'resumen_hechos',
            'personal_armado',
            'personal_activo',
            'actividades',
            'operativos',
            'puestas_disposicion',
        ];

        if (!in_array($accion, $accionesValidas, true)) {
            return $this->respuestaNoValida();
        }

        $filtros = is_array($decoded['filtros'] ?? null) ? $decoded['filtros'] : [];
        $filtros = array_merge([
            'fecha' => null,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'hora_inicio' => null,
            'hora_fin' => null,
            'tipo_hecho' => null,
            'situacion' => null,
            'tipo_operativo' => null,
            'tipo_puesta' => null,
            'estatus' => null,
            'municipio' => null,
            'delegacion_id' => null,
        ], $filtros);

        return [
            'accion' => $accion,
            'unidad_id' => isset($decoded['unidad_id']) && $decoded['unidad_id'] !== ''
                ? $decoded['unidad_id']
                : null,
            'id' => isset($decoded['id']) && $decoded['id'] !== ''
                ? $decoded['id']
                : null,
            'filtros' => $filtros,
        ];
    }

    protected function respuestaNoValida(): array
    {
        return ['accion' => 'no_valida'];
    }
}
