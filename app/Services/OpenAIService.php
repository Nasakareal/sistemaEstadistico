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
        $mensajeOriginal = trim($mensaje);
        $texto = $this->normalizarTexto($mensaje);

        if ($texto === '' || in_array($texto, ['hola', 'buen dia', 'buenos dias', 'buenas tardes', 'buenas noches'], true)) {
            return $this->respuestaNoValida();
        }

        $filtros = $this->filtrosBase();
        $filtros = array_merge($filtros, $this->resolverFechasLocales($texto));
        $unidadId = $this->resolverUnidadLocal($texto);
        $filtrosBusquedaHechos = $this->resolverFiltrosBusquedaHechosLocal($mensajeOriginal, $texto);
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

        if ($this->esConsultaTopPuestasElementos($texto)) {
            $accion = $this->esConsultaTarjetaTopPuestas($texto)
                ? 'tarjeta_top_puestas'
                : 'top_puestas_elementos';

            return $this->normalizarRespuesta([
                'accion' => $accion,
                'unidad_id' => $unidadId,
                'id' => null,
                'persona' => null,
                'posicion' => $this->resolverPosicionTopLocal($texto),
                'filtros' => $filtros,
            ]);
        }

        if ($this->contieneAlguno($texto, ['lesionados', 'lesionado'])) {
            return $this->respuestaLocal('estadistica_lesionados', $unidadId, null, $filtros);
        }

        if ($this->contieneAlguno($texto, ['fallecidos', 'fallecido', 'muertos', 'muerto'])) {
            return $this->respuestaLocal('estadistica_fallecidos', $unidadId, null, $filtros);
        }

        if ($this->contieneAlguno($texto, ['motocicletas', 'motocicleta', 'motos', 'moto'])) {
            return $this->respuestaLocal('estadistica_motocicletas', $unidadId, null, $filtros);
        }

        $situacion = $this->resolverSituacionLocal($texto);
        if ($situacion !== null && $this->esConsultaEstadistica($texto)) {
            $filtros['situacion'] = $situacion;

            return $this->respuestaLocal('estadistica_situacion', $unidadId, null, $filtros);
        }

        $tipoHecho = $this->resolverTipoHechoLocal($texto);
        if ($tipoHecho !== null && $this->esConsultaEstadistica($texto)) {
            $filtros['tipo_hecho'] = $tipoHecho;

            return $this->respuestaLocal('estadistica_tipo_hecho', $unidadId, null, $filtros);
        }

        if ($this->esConsultaDetallePersonal($texto)) {
            $persona = $this->resolverBusquedaPersonalLocal($mensajeOriginal);

            return $this->normalizarRespuesta([
                'accion' => 'detalle_personal',
                'unidad_id' => $unidadId,
                'id' => null,
                'persona' => $persona,
                'filtros' => $filtros,
            ]);
        }

        if ($this->esConsultaBusquedaHechos($texto, $filtrosBusquedaHechos)) {
            $filtros = array_merge($filtros, $filtrosBusquedaHechos);

            if ($this->esConsultaConteo($texto)) {
                $accion = 'contar_hechos';
            } else {
                $accion = 'buscar_hechos';
            }

            return $this->respuestaLocal($accion, $unidadId, null, $filtros);
        }

        if ($this->esConsultaEstadistica($texto) && $this->contieneAlguno($texto, ['hecho', 'hechos', 'siniestro', 'siniestros', 'accidente', 'accidentes'])) {
            return $this->respuestaLocal('estadistica_resumen_general', $unidadId, null, $filtros);
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
- buscar_hechos
- lista_hechos
- estadistica_hechos
- resumen_hechos
- personal_armado
- personal_activo
- detalle_personal
- top_puestas_elementos
- tarjeta_top_puestas
- estadistica_resumen_general
- estadistica_motocicletas
- estadistica_lesionados
- estadistica_fallecidos
- estadistica_situacion
- estadistica_tipo_hecho
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
  "persona": null,
  "posicion": null,
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
    "delegacion_id": null,
    "busqueda": null,
    "marca": null,
    "linea": null,
    "modelo": null,
    "color": null,
    "placa": null,
    "serie": null
  }
}

REGLAS DE ACCIÓN:
- Si pide cuántos hechos, siniestros, accidentes, choques o colisiones hay, usa contar_hechos.
- Si pide un hecho específico, usa detalle_hecho e incluye id.
- Si pide buscar, encontrar, mostrar o listar hechos por una placa, serie/NIV, marca, línea, modelo, color, conductor, calle, colonia, municipio o texto ambiguo, usa buscar_hechos.
- Si pide lista de hechos, usa lista_hechos.
- Si pide estadística, resumen o desglose de hechos, usa estadistica_hechos o resumen_hechos.
- Si pide personal armado, relación de armamento o lista de elementos armados, usa personal_armado.
- Si pide personal activo, usa personal_activo.
- Si pide expediente, ficha, perfil, tarjeta, foto, patrulla o información de un elemento, usa detalle_personal y llena persona con el nombre, número de empleado, CUP, CUIP, CURP o RFC.
- Si pide el top, ranking o elementos con más puestas a disposición, usa top_puestas_elementos.
- Si pide la tarjeta, ficha o expediente de una posición del top de puestas a disposición, usa tarjeta_top_puestas y llena posicion con un número del 1 al 20.
- Si pide resumen general de hechos, usa estadistica_resumen_general.
- Si pide lesionados, usa estadistica_lesionados.
- Si pide fallecidos, usa estadistica_fallecidos.
- Si pide motocicletas o motos, usa estadistica_motocicletas.
- Si pide una estadística por situación, usa estadistica_situacion y llena situacion.
- Si pide una estadística por tipo de hecho, usa estadistica_tipo_hecho y llena tipo_hecho.
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
- Para búsquedas ambiguas de hechos, llena busqueda con el texto buscado.
- Si identifica un dato vehicular explícito, llena marca, linea, modelo, color, placa o serie según corresponda.

SINÓNIMOS DE OPERATIVOS DE CARRETERAS:
{$sinonimosOperativos}

TIPOS DE OPERATIVO VÁLIDOS EN CARRETERAS:
{$tiposOperativos}

EJEMPLOS:
Usuario: cuantos hechos hay hoy
Respuesta: {"accion":"contar_hechos","unidad_id":null,"id":null,"persona":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: dame el hecho 59564
Respuesta: {"accion":"detalle_hecho","unidad_id":null,"id":59564,"persona":null,"filtros":{"fecha":null,"fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: dame mi personal armado
Respuesta: {"accion":"personal_armado","unidad_id":null,"id":null,"persona":null,"filtros":{"fecha":null,"fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantas actividades hubo hoy
Respuesta: {"accion":"actividades","unidad_id":null,"id":null,"persona":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantos operativos hubo hoy en carreteras
Respuesta: {"accion":"operativos","unidad_id":4,"id":null,"persona":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantas puestas a disposición hubo hoy
Respuesta: {"accion":"puestas_disposicion","unidad_id":null,"id":null,"persona":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: dame el expediente del elemento Juan Pérez
Respuesta: {"accion":"detalle_personal","unidad_id":null,"id":null,"persona":"Juan Pérez","filtros":{"fecha":null,"fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: dame el top de elementos con más puestas a disposición este mes
Respuesta: {"accion":"top_puestas_elementos","unidad_id":null,"id":null,"persona":null,"posicion":null,"filtros":{"fecha":null,"fecha_inicio":"{$inicioMes->toDateString()}","fecha_fin":"{$finMes->toDateString()}","hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: mándame la tarjeta del segundo lugar del top de puestas
Respuesta: {"accion":"tarjeta_top_puestas","unidad_id":null,"id":null,"persona":null,"posicion":2,"filtros":{"fecha":null,"fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: cuantos lesionados hubo hoy
Respuesta: {"accion":"estadistica_lesionados","unidad_id":null,"id":null,"persona":null,"filtros":{"fecha":"{$hoy->toDateString()}","fecha_inicio":null,"fecha_fin":null,"hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

Usuario: estadística de motocicletas este mes
Respuesta: {"accion":"estadistica_motocicletas","unidad_id":null,"id":null,"persona":null,"filtros":{"fecha":null,"fecha_inicio":"{$inicioMes->toDateString()}","fecha_fin":"{$finMes->toDateString()}","hora_inicio":null,"hora_fin":null,"tipo_hecho":null,"situacion":null,"tipo_operativo":null,"tipo_puesta":null,"estatus":null,"municipio":null,"delegacion_id":null}}

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
            'buscar_hechos',
            'lista_hechos',
            'estadistica_hechos',
            'resumen_hechos',
            'personal_armado',
            'personal_activo',
            'detalle_personal',
            'top_puestas_elementos',
            'tarjeta_top_puestas',
            'estadistica_resumen_general',
            'estadistica_motocicletas',
            'estadistica_lesionados',
            'estadistica_fallecidos',
            'estadistica_situacion',
            'estadistica_tipo_hecho',
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
            'busqueda' => null,
            'marca' => null,
            'linea' => null,
            'modelo' => null,
            'color' => null,
            'placa' => null,
            'serie' => null,
        ], $filtros);

        return [
            'accion' => $accion,
            'unidad_id' => isset($decoded['unidad_id']) && $decoded['unidad_id'] !== ''
                ? $decoded['unidad_id']
                : null,
            'id' => isset($decoded['id']) && $decoded['id'] !== ''
                ? $decoded['id']
                : null,
            'persona' => isset($decoded['persona']) && trim((string) $decoded['persona']) !== ''
                ? trim((string) $decoded['persona'])
                : null,
            'posicion' => isset($decoded['posicion'])
                && is_numeric($decoded['posicion'])
                && (int) $decoded['posicion'] >= 1
                && (int) $decoded['posicion'] <= 20
                    ? (int) $decoded['posicion']
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
            'busqueda' => null,
            'marca' => null,
            'linea' => null,
            'modelo' => null,
            'color' => null,
            'placa' => null,
            'serie' => null,
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

    protected function resolverSituacionLocal(string $texto): ?string
    {
        if ($this->contieneAlguno($texto, ['resuelto', 'resueltos'])) {
            return 'RESUELTO';
        }

        if ($this->contieneAlguno($texto, ['pendiente', 'pendientes'])) {
            return 'PENDIENTE';
        }

        if ($this->contieneAlguno($texto, ['turnado', 'turnados'])) {
            return 'TURNADO';
        }

        if ($this->contieneAlguno($texto, ['reporte', 'reportes'])) {
            return 'REPORTE';
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

    protected function esConsultaBusquedaHechos(string $texto, array $filtrosBusqueda): bool
    {
        if (empty(array_filter($filtrosBusqueda, fn ($value) => trim((string) $value) !== ''))) {
            return false;
        }

        $mencionaHechos = $this->contieneAlguno($texto, [
            'hecho',
            'hechos',
            'siniestro',
            'siniestros',
            'accidente',
            'accidentes',
            'choque',
            'choques',
            'colision',
            'colisiones',
        ]);

        if (!$mencionaHechos) {
            return false;
        }

        return $this->contieneAlguno($texto, [
            'busca',
            'buscar',
            'busqueda',
            'encuentra',
            'encontrar',
            'dame',
            'muestra',
            'muestrame',
            'lista',
            'listado',
            'consulta',
            'con',
            'por',
            'placa',
            'placas',
            'marca',
            'linea',
            'modelo',
            'color',
            'serie',
            'niv',
            'vehiculo',
            'vehiculos',
        ]);
    }

    protected function esConsultaEstadistica(string $texto): bool
    {
        return $this->contieneAlguno($texto, [
            'estadistica',
            'estadisticas',
            'resumen',
            'desglose',
            'cuantos',
            'cuantas',
            'conteo',
            'total',
        ]);
    }

    protected function esConsultaConteo(string $texto): bool
    {
        return $this->contieneAlguno($texto, [
            'cuantos',
            'cuantas',
            'conteo',
            'total',
        ]);
    }

    protected function esConsultaDetallePersonal(string $texto): bool
    {
        if ($this->contieneAlguno($texto, [
            'expediente',
            'ficha',
            'perfil',
            'tarjeta del elemento',
            'tarjeta de personal',
            'tarjeta del policia',
            'tarjeta del oficial',
            'tarjeta del agente',
            'detalle del elemento',
            'detalle de personal',
            'datos del elemento',
            'informacion del elemento',
            'info del elemento',
            'foto del elemento',
            'patrulla del elemento',
            'asignacion del elemento',
        ])) {
            return true;
        }

        return (bool) preg_match(
            '/\b(?:expediente|ficha|perfil|tarjeta|detalle|datos|informacion|info|foto|patrulla|asignacion)\s+(?:de|del|de la)\s+/u',
            $texto
        );
    }

    protected function esConsultaTopPuestasElementos(string $texto): bool
    {
        $mencionaPuestas = $this->contieneAlguno($texto, [
            'puesta a disposicion',
            'puestas a disposicion',
            'puesta disposicion',
            'puestas disposicion',
            'puestas',
        ]);

        $mencionaRanking = $this->contieneAlguno($texto, [
            'top',
            'ranking',
            'primer lugar',
            'primero lugar',
            'segundo lugar',
            'tercer lugar',
            'mas puestas',
            'mayor numero de puestas',
        ]);

        return $mencionaPuestas && $mencionaRanking;
    }

    protected function esConsultaTarjetaTopPuestas(string $texto): bool
    {
        return $this->contieneAlguno($texto, [
            'tarjeta',
            'ficha',
            'expediente',
            'perfil',
            'foto',
            'datos del',
            'informacion del',
        ]);
    }

    protected function resolverPosicionTopLocal(string $texto): ?int
    {
        $ordinales = [
            1 => ['primer lugar', 'primero lugar', 'primera posicion', 'primero', 'primera'],
            2 => ['segundo lugar', 'segunda posicion', 'segundo', 'segunda'],
            3 => ['tercer lugar', 'tercero lugar', 'tercera posicion', 'tercero', 'tercera'],
        ];

        foreach ($ordinales as $posicion => $terminos) {
            if ($this->contieneAlguno($texto, $terminos)) {
                return $posicion;
            }
        }

        if (preg_match('/\b(?:top|lugar|posicion|numero|no)\s*#?\s*(\d{1,2})\b/u', $texto, $matches)) {
            $posicion = (int) $matches[1];

            return $posicion >= 1 && $posicion <= 20 ? $posicion : null;
        }

        return $this->esConsultaTarjetaTopPuestas($texto) ? 1 : null;
    }

    protected function resolverBusquedaPersonalLocal(string $mensaje): ?string
    {
        $mensaje = trim($mensaje);

        if ($mensaje === '') {
            return null;
        }

        if (preg_match(
            '/(?:expediente|ficha|perfil|tarjeta|detalle|datos|informaci[oó]n|info|foto|patrulla|asignaci[oó]n)\s+(?:de|del|de la)\s+(?:elemento|personal|polic[ií]a|oficial|agente|comandante|subdirector)?\s*(.+)$/iu',
            $mensaje,
            $matches
        )) {
            return $this->limpiarBusquedaPersonal($matches[1]);
        }

        if (preg_match(
            '/(?:expediente|ficha|perfil|tarjeta|detalle|datos|informaci[oó]n|info|foto|patrulla|asignaci[oó]n)\s+(?:elemento|personal|polic[ií]a|oficial|agente|comandante|subdirector)?\s*(.+)$/iu',
            $mensaje,
            $matches
        )) {
            return $this->limpiarBusquedaPersonal($matches[1]);
        }

        if (preg_match(
            '/\b(?:elemento|personal|polic[ií]a|oficial|agente|comandante|subdirector)\s+(.+)$/iu',
            $mensaje,
            $matches
        )) {
            return $this->limpiarBusquedaPersonal($matches[1]);
        }

        if (preg_match('/\b(?:n[uú]mero\s+de\s+empleado|no\.?\s*empleado|empleado|cup|cuip|curp|rfc|id)\s*[:#-]?\s*([A-Za-z0-9-]+)/iu', $mensaje, $matches)) {
            return $this->limpiarBusquedaPersonal($matches[1]);
        }

        return null;
    }

    protected function resolverFiltrosBusquedaHechosLocal(string $mensaje, string $texto): array
    {
        $filtros = [];
        $mensaje = trim($mensaje);

        if ($mensaje === '') {
            return $filtros;
        }

        $patrones = [
            'placa' => '/\bplacas?\s*(?:del?|:|#)?\s*([A-Za-z0-9-]{3,15})\b/iu',
            'serie' => '/\b(?:serie|niv)\s*(?:del?|:|#)?\s*([A-Za-z0-9-]{5,25})\b/iu',
            'marca' => '/\bmarca\s+(?:del?\s+)?([A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .-]{2,40})/iu',
            'linea' => '/\b(?:linea|línea|submarca)\s+(?:del?\s+)?([A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .-]{2,40})/iu',
            'modelo' => '/\bmodelo\s+(?:del?\s+)?([A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .-]{2,40})/iu',
            'color' => '/\bcolor\s+([A-Za-zÁÉÍÓÚÜÑáéíóúüñ .-]{3,30})/iu',
        ];

        foreach ($patrones as $campo => $patron) {
            if (preg_match($patron, $mensaje, $matches)) {
                $valor = $this->limpiarBusquedaHecho($matches[1]);

                if ($valor !== null) {
                    $filtros[$campo] = $valor;
                }
            }
        }

        if (!isset($filtros['color'])) {
            $color = $this->resolverColorLocal($texto);

            if ($color !== null) {
                $filtros['color'] = $color;
            }
        }

        $busqueda = $this->resolverBusquedaHechoLocal($mensaje);

        if ($busqueda !== null) {
            $filtros['busqueda'] = $busqueda;
        }

        return $filtros;
    }

    protected function resolverBusquedaHechoLocal(string $mensaje): ?string
    {
        $valor = trim($mensaje);

        if (preg_match(
            '/\b(?:busca|buscar|buscame|búscame|encuentra|encontrar|dame|muestra|mu[eé]strame|lista|listado|consulta|quiero|necesito)\b(.+)$/iu',
            $valor,
            $matches
        )) {
            $valor = trim($matches[1]);
        }

        $valor = preg_replace(
            '/\b(?:cu[aá]ntos?|cu[aá]ntas?|conteo|total|hechos?|siniestros?|accidentes?|choques?|colisiones?|veh[ií]culos?|carros?|autos?|camionetas?|motos?|motocicletas?|por|con|en|de|del|donde|que|tengan|tiene|hubo|hay|marca|linea|línea|submarca|modelo|color|placas?|serie|niv|hoy|ayer|antier|este\s+mes|mes\s+pasado|mes\s+anterior|en\s+siniestros|siniestros)\b/iu',
            ' ',
            $valor
        );
        $valor = preg_replace('/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/iu', ' ', (string) $valor);
        $valor = preg_replace('/\b\d{4}-\d{2}-\d{2}\b/u', ' ', (string) $valor);
        $valor = preg_replace('/\s+/', ' ', (string) $valor);
        $valor = trim((string) $valor, " \t\n\r\0\x0B:,.?!¿¡");

        if ($valor === '' || mb_strlen($valor, 'UTF-8') < 2) {
            return null;
        }

        return $valor;
    }

    protected function limpiarBusquedaHecho(string $valor): ?string
    {
        $valor = preg_replace('/\b(?:hoy|ayer|antier|este\s+mes|mes\s+pasado|mes\s+anterior|marca|linea|línea|submarca|modelo|color|placas?|serie|niv|en|de|del|la|el|los|las|por|con)\b.*$/iu', '', $valor);
        $valor = trim((string) $valor, " \t\n\r\0\x0B:,.?!¿¡");

        return $valor !== '' ? $valor : null;
    }

    protected function resolverColorLocal(string $texto): ?string
    {
        $colores = [
            'blanco',
            'negro',
            'gris',
            'plata',
            'rojo',
            'azul',
            'verde',
            'amarillo',
            'naranja',
            'cafe',
            'café',
            'marron',
            'marrón',
            'dorado',
            'vino',
            'guinda',
            'beige',
            'crema',
        ];

        foreach ($colores as $color) {
            if ($this->contieneAlguno($texto, [$color])) {
                return $color;
            }
        }

        return null;
    }

    protected function limpiarBusquedaPersonal(string $valor): ?string
    {
        $valor = trim((string) preg_replace('/^(?:del?|de la|la|el)\s+/iu', '', trim($valor)));
        $valor = trim($valor, " \t\n\r\0\x0B:,.?!");

        return $valor !== '' ? $valor : null;
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

        $texto = str_replace(["'", '`', '´'], '', $texto);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

        return trim((string) preg_replace('/\s+/', ' ', (string) $texto));
    }

    protected function respuestaNoValida(): array
    {
        return ['accion' => 'no_valida'];
    }
}
