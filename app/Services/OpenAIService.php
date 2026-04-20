<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function interpretar($mensaje)
    {
        $local = $this->interpretarLocal((string) $mensaje);

        if (($local['accion'] ?? 'no_valida') !== 'no_valida') {
            return $local;
        }

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

    protected function interpretarLocal(string $mensaje): array
    {
        $texto = $this->normalizarTexto($mensaje);

        if ($texto === '' || in_array($texto, ['hola', 'buen dia', 'buenos dias', 'buenas tardes', 'buenas noches'], true)) {
            return $this->respuestaNoValida();
        }

        $filtros = $this->filtrosBase();
        $filtros = array_merge($filtros, $this->resolverFechasLocales($texto));
        $unidadId = $this->resolverUnidadLocal($texto);
        $id = null;

        if (preg_match('/\b(?:hecho|folio|id)\s*(?:numero|num|no\.?)?\s*(\d+)\b/u', $texto, $matches)) {
            $id = (int) $matches[1];

            return $this->respuestaLocal('detalle_hecho', $unidadId, $id, $filtros);
        }

        if ($this->contieneAlguno($texto, ['personal armado', 'armamento', 'elementos armados', 'relacion de personal armado', 'relacion armado'])) {
            return $this->respuestaLocal('personal_armado', $unidadId, null, $filtros);
        }

        if ($this->contieneAlguno($texto, ['personal activo', 'elementos activos'])) {
            return $this->respuestaLocal('personal_activo', $unidadId, null, $filtros);
        }

        if ($this->contieneAlguno($texto, ['puesta a disposicion', 'puestas a disposicion', 'puesta disposicion', 'puestas disposicion'])) {
            return $this->respuestaLocal('puestas_disposicion', $unidadId, null, $filtros);
        }

        if ($this->contieneAlguno($texto, ['operativo', 'operativos', 'dispositivo', 'dispositivos', 'guardianes del camino', 'psv', 'rsv', 'casco', 'cinturon', 'carrusel', 'cordillera', 'asiento seguro', 'acompanamiento', 'abanderamiento', 'auxilio vial', 'caballero del camino', 'c5'])) {
            $filtros['tipo_operativo'] = $this->resolverTipoOperativoLocal($texto);

            return $this->respuestaLocal(
                $this->esLista($texto) ? 'lista_operativos' : 'operativos',
                $unidadId ?: 4,
                null,
                $filtros
            );
        }

        if ($this->contieneAlguno($texto, ['actividad', 'actividades', 'apoyo', 'apoyos', 'labor', 'labores', 'proximidad social'])) {
            return $this->respuestaLocal(
                $this->esLista($texto) ? 'lista_actividades' : 'actividades',
                $unidadId,
                null,
                $filtros
            );
        }

        if ($this->contieneAlguno($texto, ['hecho', 'hechos', 'siniestro', 'siniestros', 'choque', 'choques', 'accidente', 'accidentes', 'colision', 'colisiones', 'volcadura', 'volcaduras', 'atropello', 'atropellos'])) {
            $tipoHecho = $this->resolverTipoHechoLocal($texto);

            if ($tipoHecho !== null) {
                $filtros['tipo_hecho'] = $tipoHecho;
            }

            if ($this->esLista($texto)) {
                $accion = 'lista_hechos';
            } elseif ($this->contieneAlguno($texto, ['estadistica', 'estadisticas', 'resumen', 'desglose'])) {
                $accion = 'estadistica_hechos';
            } else {
                $accion = 'contar_hechos';
            }

            return $this->respuestaLocal($accion, $unidadId, null, $filtros);
        }

        return $this->respuestaNoValida();
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
- lista_actividades
- operativos
- lista_operativos
- puestas_disposicion
- lista_puestas_disposicion
- detalle_puesta_disposicion

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
- Si pide cuántos hechos, siniestros, accidentes, choques o colisiones hay, usa contar_hechos.
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
- Si menciona choques o colisiones sin especificar subtipo, llena tipo_hecho = "CHOQUES".
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
            'estadistica_actividades',
            'lista_actividades',
            'operativos',
            'estadistica_operativos',
            'lista_operativos',
            'puestas_disposicion',
            'estadistica_puestas_disposicion',
            'lista_puestas_disposicion',
            'detalle_puesta_disposicion',
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

    protected function respuestaLocal(string $accion, ?int $unidadId, ?int $id, array $filtros): array
    {
        return $this->normalizarRespuesta([
            'accion' => $accion,
            'unidad_id' => $unidadId,
            'id' => $id,
            'filtros' => $filtros,
        ]);
    }

    protected function filtrosBase(): array
    {
        return [
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
        ];
    }

    protected function resolverUnidadLocal(string $texto): ?int
    {
        if ($this->contieneAlguno($texto, ['siniestros', 'siniestro'])) {
            return 1;
        }

        if ($this->contieneAlguno($texto, ['delegaciones', 'delegacion'])) {
            return 2;
        }

        if ($this->contieneAlguno($texto, ['coordinacion', 'seguridad vial'])) {
            return 3;
        }

        if ($this->contieneAlguno($texto, ['carreteras', 'carretera', 'proteccion a carreteras'])) {
            return 4;
        }

        if ($this->contieneAlguno($texto, ['vialidades', 'vialidad', 'vialidades urbanas'])) {
            return 5;
        }

        return null;
    }

    protected function resolverFechasLocales(string $texto): array
    {
        $filtros = [];
        $hoy = now();
        $rangoFechaConMes = false;

        if (preg_match('/\b(?:del\s+)?(\d{1,2})\s+(?:al|a)\s+(\d{1,2})\s+(?:de\s+)?(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)(?:\s+de\s+(\d{4}))?\b/u', $texto, $matches)) {
            $rangoFechaConMes = true;
            $mes = $this->numeroMes($matches[3]);
            $anio = !empty($matches[4]) ? (int) $matches[4] : (int) $hoy->year;

            if ($mes) {
                $inicio = now()->setDate($anio, $mes, min((int) $matches[1], (int) $matches[2]))->startOfDay();
                $fin = now()->setDate($anio, $mes, max((int) $matches[1], (int) $matches[2]))->startOfDay();

                $filtros['fecha_inicio'] = $inicio->toDateString();
                $filtros['fecha_fin'] = $fin->toDateString();
            }
        } elseif (preg_match('/\b(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)(?:\s+de\s+(\d{4}))?\b/u', $texto, $matches)) {
            $mes = $this->numeroMes($matches[1]);
            $anio = !empty($matches[2]) ? (int) $matches[2] : (int) $hoy->year;

            if ($mes) {
                $inicio = now()->setDate($anio, $mes, 1)->startOfMonth();
                $fin = now()->setDate($anio, $mes, 1)->endOfMonth();

                $filtros['fecha_inicio'] = $inicio->toDateString();
                $filtros['fecha_fin'] = $fin->toDateString();
            }
        } elseif ($this->contieneAlguno($texto, ['hoy'])) {
            $filtros['fecha'] = $hoy->toDateString();
        } elseif ($this->contieneAlguno($texto, ['ayer'])) {
            $filtros['fecha'] = now()->subDay()->toDateString();
        } elseif ($this->contieneAlguno($texto, ['antier', 'anteayer'])) {
            $filtros['fecha'] = now()->subDays(2)->toDateString();
        } elseif ($this->contieneAlguno($texto, ['este mes'])) {
            $filtros['fecha_inicio'] = now()->startOfMonth()->toDateString();
            $filtros['fecha_fin'] = now()->endOfMonth()->toDateString();
        } elseif ($this->contieneAlguno($texto, ['mes pasado', 'mes anterior'])) {
            $filtros['fecha_inicio'] = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $filtros['fecha_fin'] = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
        } elseif ($this->contieneAlguno($texto, ['este ano', 'este año'])) {
            $filtros['fecha_inicio'] = $hoy->copy()->startOfYear()->toDateString();
            $filtros['fecha_fin'] = $hoy->copy()->endOfYear()->toDateString();
        }

        if (!$rangoFechaConMes && preg_match('/\b(?:de\s+)?(\d{1,2})(?::(\d{2}))?\s*(?:a|al|-)\s*(\d{1,2})(?::(\d{2}))?\s*(?:hrs?|horas?)?\b/u', $texto, $matches)) {
            $h1 = max(0, min(23, (int) $matches[1]));
            $m1 = isset($matches[2]) && $matches[2] !== '' ? max(0, min(59, (int) $matches[2])) : 0;
            $h2 = max(0, min(23, (int) $matches[3]));
            $m2 = isset($matches[4]) && $matches[4] !== '' ? max(0, min(59, (int) $matches[4])) : 0;

            $filtros['hora_inicio'] = sprintf('%02d:%02d:00', $h1, $m1);
            $filtros['hora_fin'] = sprintf('%02d:%02d:00', $h2, $m2);
        }

        return $filtros;
    }

    protected function resolverTipoHechoLocal(string $texto): ?string
    {
        if ($this->contieneAlguno($texto, ['choque por alcance', 'colision por alcance'])) {
            return 'COLISIÓN POR ALCANCE';
        }

        if ($this->contieneAlguno($texto, ['cambio de carril'])) {
            return 'COLISIÓN POR CAMBIO DE CARRIL';
        }

        if ($this->contieneAlguno($texto, ['invasion de carril'])) {
            return 'COLISIÓN POR INVASIÓN DE CARRIL';
        }

        if ($this->contieneAlguno($texto, ['corte de circulacion'])) {
            return 'COLISIÓN POR CORTE DE CIRCULACIÓN';
        }

        if ($this->contieneAlguno($texto, ['objeto fijo'])) {
            return 'COLISIÓN CONTRA OBJETO FIJO';
        }

        if ($this->contieneAlguno($texto, ['reversa'])) {
            return 'COLISIÓN POR MANIOBRA DE REVERSA';
        }

        if ($this->contieneAlguno($texto, ['semaforo'])) {
            return 'COLISIÓN POR NO RESPETAR SEMÁFORO';
        }

        if ($this->contieneAlguno($texto, ['volcadura', 'volcaduras'])) {
            return 'VOLCADURA';
        }

        if ($this->contieneAlguno($texto, ['atropello', 'atropellos', 'peaton'])) {
            return 'COLISIÓN CON PEATÓN';
        }

        if ($this->contieneAlguno($texto, ['choque', 'choques', 'colision', 'colisiones'])) {
            return 'CHOQUES';
        }

        return null;
    }

    protected function resolverTipoOperativoLocal(string $texto): ?string
    {
        foreach ($this->guardianesDispositivos() as $dispositivo) {
            $nombre = (string) ($dispositivo['nombre'] ?? '');
            $aliases = (array) ($dispositivo['aliases'] ?? []);
            $candidatos = array_merge([$nombre], $aliases);

            foreach ($candidatos as $candidato) {
                $normalizado = $this->normalizarTexto((string) $candidato);

                if ($normalizado !== '' && strpos($texto, $normalizado) !== false) {
                    return $nombre ?: (string) $candidato;
                }
            }
        }

        return null;
    }

    protected function numeroMes(string $mes): ?int
    {
        $meses = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,
        ];

        return $meses[$this->normalizarTexto($mes)] ?? null;
    }

    protected function esLista(string $texto): bool
    {
        return $this->contieneAlguno($texto, ['lista', 'listado', 'relacion', 'relación', 'dame los', 'dame las', 'muestrame', 'muéstrame']);
    }

    protected function contieneAlguno(string $texto, array $terminos): bool
    {
        foreach ($terminos as $termino) {
            if (strpos($texto, $this->normalizarTexto((string) $termino)) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

        if ($ascii !== false) {
            $texto = $ascii;
        }

        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

        return trim((string) preg_replace('/\s+/', ' ', (string) $texto));
    }

    protected function respuestaNoValida(): array
    {
        return ['accion' => 'no_valida'];
    }
}
