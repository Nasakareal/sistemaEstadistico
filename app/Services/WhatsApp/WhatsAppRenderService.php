<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class WhatsAppRenderService
{
    public function renderReporte(?int $unidadId, string $asunto, string $periodo, array $lineas): string
    {
        $bloques = [];
        $bloques[] = 'GUARDIA CIVIL';
        $bloques[] = 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL.';

        $unidad = $this->lineaUnidadPorId($unidadId);

        if ($unidad !== '') {
            $bloques[] = $unidad;
        }

        $resultado = ['RESULTADO:'];

        foreach ($lineas as $linea) {
            $linea = trim((string) $linea);

            if ($linea === '') {
                continue;
            }

            $linea = preg_replace('/^\s*[-*]\s*/', '', $linea);
            $resultado[] = '* ' . $linea;
        }

        if (count($resultado) === 1) {
            $resultado[] = '* Sin resultados.';
        }

        $bloques[] = 'ASUNTO: ' . trim($asunto) . '.';
        $bloques[] = 'PERIODO: ' . trim($periodo) . '.';
        $bloques[] = implode("\n\n", [
            $resultado[0],
            implode("\n", array_slice($resultado, 1)),
        ]);
        $bloques[] = 'PARA CONOCIMIENTO DE LA SUPERIORIDAD.';

        return implode("\n\n", array_values(array_filter($bloques, fn ($item) => trim((string) $item) !== '')));
    }

    public function renderDetalleHecho(Hechos $hecho): array
    {
        $detalle = $this->obtenerDetalleHecho($hecho);

        $bloques = [];

        $bloques[] = 'GUARDIA CIVIL';
        $bloques[] = $detalle['coordinacion'] ?? '';

        if (!empty($detalle['unidad'])) {
            $bloques[] = $detalle['unidad'];
        }

        $bloques[] = $detalle['municipio'] ?? '';

        if (!empty($detalle['sector'])) {
            $bloques[] = $detalle['sector'];
        }

        $bloques[] = 'TEMA: ' . ($detalle['tema'] ?? 'HECHO DE TRÁNSITO');
        $bloques[] = $detalle['descripcion'] ?? '';

        if (!empty($detalle['vehiculos_texto'])) {
            $bloques[] = 'Lugar donde se encuentran:';
            $bloques[] = $detalle['vehiculos_texto'];
        }

        if (!empty($detalle['estado'])) {
            $bloques[] = 'Hecho ' . $detalle['estado'] . '.';
        }

        $ubicacionExtra = [];

        if (!empty($detalle['ubicacion'])) {
            $ubicacionExtra[] = 'Ubicación: ' . $detalle['ubicacion'];
        }

        if (!empty($detalle['google_maps'])) {
            $ubicacionExtra[] = 'Google Maps: ' . $detalle['google_maps'];
        }

        if (!empty($ubicacionExtra)) {
            $bloques[] = implode("\n", $ubicacionExtra);
        }

        if (!empty($detalle['informa'])) {
            $bloques[] = 'INFORMA ' . $detalle['informa'];
        }

        $bloques = array_values(array_filter($bloques, fn ($item) => $item !== null && trim((string) $item) !== ''));

        return [
            'text' => implode("\n\n", $bloques),
            'images' => $detalle['fotos'] ?? [],
        ];
    }

    public function renderDetallePersonal(Personal $personal): array
    {
        $detalle = $this->obtenerDetallePersonal($personal);
        $bloques = [
            'GUARDIA CIVIL',
            'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL',
        ];

        if ($detalle['unidad'] !== '') {
            $bloques[] = $detalle['unidad'];
        }

        $bloques[] = 'EXPEDIENTE DE PERSONAL';
        $bloques[] = implode("\n", $detalle['lineas']);
        $bloques[] = 'PARA CONOCIMIENTO DE LA SUPERIORIDAD.';

        return [
            'text' => implode("\n\n", array_values(array_filter($bloques, fn ($item) => trim((string) $item) !== ''))),
            'images' => $detalle['imagenes'],
        ];
    }

    protected function obtenerDetalleHecho(Hechos $hecho): array
    {
        $hecho->loadMissing(['vehiculos', 'unidadOrganizacional']);

        $ubicacionPartes = array_filter([
            $hecho->calle,
            $hecho->colonia ? 'col. ' . $hecho->colonia : null,
        ]);

        $descripcion = trim(implode(' ', array_filter([
            (string) $hecho->fecha,
            $this->formatearHora((string) $hecho->hora),
            'Hrs. Guardia Civil toma conocimiento en',
            implode(', ', $ubicacionPartes) . '.',
        ])));

        $lat = $hecho->lat;
        $lng = $hecho->lng;
        $googleMaps = null;
        $ubicacion = null;

        if (!is_null($lat) && !is_null($lng) && $lat !== '' && $lng !== '') {
            $ubicacion = "{$lat}, {$lng}";
            $googleMaps = "https://www.google.com/maps?q={$lat},{$lng}";
        }

        $vehiculosTexto = [];
        $fotosVehiculos = [];

        foreach (($hecho->vehiculos ?? []) as $index => $vehiculo) {
            $etiqueta = chr(65 + $index) . ')';

            $lineasVehiculo = [];
            $lineasVehiculo[] = 'VEHÍCULO ' . $etiqueta;
            $lineasVehiculo[] = $this->buildVehiculoDescripcion($vehiculo);

            $ocupantes = $this->buildVehiculoOcupantes($vehiculo);

            if ($ocupantes !== '') {
                $lineasVehiculo[] = $ocupantes;
            }

            $vehiculosTexto[] = implode("\n", array_filter($lineasVehiculo, fn ($item) => trim((string) $item) !== ''));
            $fotosVehiculos = array_merge($fotosVehiculos, $this->extraerUrlsDesdeCampo($vehiculo->fotos ?? null));
        }

        $fotos = array_values(array_unique(array_filter(array_merge(
            $this->extraerUrlsDesdeCampo($hecho->foto_lugar),
            $this->extraerUrlsDesdeCampo($hecho->foto_situacion),
            $fotosVehiculos
        ))));

        return [
            'coordinacion' => 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL',
            'unidad' => $this->resolverLineaUnidad($hecho),
            'municipio' => (string) ($hecho->municipio ?: 'MORELIA'),
            'sector' => $hecho->sector ? 'SECTOR ' . $hecho->sector : null,
            'tema' => 'HECHO DE TRÁNSITO CLASIFICADO COMO ' . mb_strtoupper((string) ($hecho->tipo_hecho ?: 'SIN CLASIFICACIÓN'), 'UTF-8'),
            'descripcion' => $descripcion,
            'vehiculos_texto' => implode("\n\n", $vehiculosTexto),
            'estado' => mb_strtoupper((string) ($hecho->situacion ?: 'SIN ESTADO'), 'UTF-8'),
            'ubicacion' => $ubicacion,
            'google_maps' => $googleMaps,
            'informa' => $hecho->unidad ? 'UNIDAD ' . $hecho->unidad : ($hecho->perito ?: null),
            'fotos' => $fotos,
        ];
    }

    protected function obtenerDetallePersonal(Personal $personal): array
    {
        $personal->loadMissing([
            'unidad',
            'turno',
            'patrulla',
            'user',
            'fotoPrincipal',
            'fotos',
            'documentos.documentoTipo',
            'asignaciones.armamento',
        ]);

        $nombre = $personal->nombre_completo;

        $patrulla = $personal->patrulla ?: optional($personal->user)->patrulla;
        $asignacionActiva = collect($personal->asignaciones ?? [])
            ->first(function ($asignacion) {
                return (bool) ($asignacion->activo ?? false) || empty($asignacion->fecha_fin);
            });
        $armamento = $asignacionActiva ? $asignacionActiva->armamento : null;

        $lineas = [
            'Nombre: ' . ($nombre !== '' ? $nombre : 'SIN NOMBRE'),
            'Estatus: ' . ($personal->estatus ?: 'SIN ESTATUS'),
            'Grado / puesto: ' . trim(($personal->grado ?: 'S/G') . ' / ' . ($personal->puesto ?: 'S/P')),
            'Número de empleado: ' . ($personal->numero_empleado ?: 'S/N'),
            'CUP: ' . ($personal->cup ?: 'S/D'),
            'CUIP: ' . ($personal->cuip ?: 'S/D'),
            'Categoría: ' . ($personal->categoria ?: 'S/D'),
            'Unidad: ' . (optional($personal->unidad)->nombre ?: 'SIN UNIDAD'),
            'Turno: ' . (optional($personal->turno)->nombre ?: 'SIN TURNO'),
            'Adscripción: ' . ($personal->adscripcion ?: 'SIN ADSCRIPCIÓN'),
            'Área: ' . ($personal->area ?: 'SIN ÁREA'),
        ];

        if ($patrulla) {
            $patrullaPartes = array_filter([
                $patrulla->numero_economico ?: null,
                trim(implode(' ', array_filter([
                    $patrulla->marca ?? null,
                    $patrulla->linea ?? null,
                    $patrulla->modelo ?? null,
                ]))),
                $patrulla->placas ? 'Placas ' . $patrulla->placas : null,
            ]);

            $lineas[] = 'Patrulla a cargo: ' . implode(' | ', $patrullaPartes);
        } else {
            $lineas[] = 'Patrulla a cargo: SIN ASIGNACIÓN';
        }

        if ($asignacionActiva) {
            $asignacionResumen = array_filter([
                $asignacionActiva->comisionado_a ? 'Comisionado a ' . $asignacionActiva->comisionado_a : null,
                $asignacionActiva->municipio_localidad_servicio ?: null,
                $asignacionActiva->funciones ?: null,
                $asignacionActiva->horario ? 'Horario ' . $asignacionActiva->horario : null,
            ]);

            if (!empty($asignacionResumen)) {
                $lineas[] = 'Asignación actual: ' . implode(' | ', $asignacionResumen);
            }
        }

        if ($armamento) {
            $armamentoResumen = array_filter([
                trim(implode(' ', array_filter([
                    $armamento->tipo ?? null,
                    $armamento->marca ?? null,
                    $armamento->modelo ?? null,
                ]))),
                $armamento->matricula ? 'Matrícula ' . $armamento->matricula : null,
                $armamento->calibre ? 'Calibre ' . $armamento->calibre : null,
            ]);

            $lineas[] = 'Armamento actual: ' . implode(' | ', $armamentoResumen);
        }

        $documentosLineas = $this->lineasDocumentosPersonal($personal);
        $lineas[] = empty($documentosLineas)
            ? 'Documentos subidos: SIN ARCHIVOS REGISTRADOS'
            : 'Documentos subidos:';
        $lineas = array_merge($lineas, $documentosLineas);

        $imagenes = $this->urlsTemporalesFotosPersonal($personal);

        return [
            'unidad' => $this->lineaUnidadPorId($personal->unidad_id ? (int) $personal->unidad_id : null),
            'lineas' => $lineas,
            'imagenes' => array_slice($imagenes, 0, 3),
        ];
    }

    protected function lineasDocumentosPersonal(Personal $personal): array
    {
        try {
            $personal->loadMissing(['documentos.documentoTipo']);

            $lineas = [];
            $rutasVistas = [];
            $documentos = collect($personal->documentos ?? [])
                ->sortByDesc(fn ($documento) => (bool) ($documento->activo ?? false))
                ->values();

            foreach ($documentos as $documento) {
                if (!$documento instanceof PersonalDocumento || !$documento->id) {
                    continue;
                }

                foreach ($this->archivosDocumentoPersonal($documento, $rutasVistas) as $archivo) {
                    $lineas[] = $this->lineaDocumentoPersonal($documento, $archivo);
                }
            }

            return $lineas;
        } catch (\Throwable $e) {
            Log::warning('WA personal document URLs error', [
                'personal_id' => $personal->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function archivosDocumentoPersonal(PersonalDocumento $documento, array &$rutasVistas): array
    {
        $candidatos = [
            [
                'archivo' => 'comision',
                'ruta' => $documento->archivo_oficio_comision,
                'label' => 'Oficio de comisión',
                'folio' => $documento->oficio_comision_secretario ?: $documento->numero,
                'fecha' => $documento->fecha_oficio,
                'nombre' => null,
            ],
            [
                'archivo' => 'asignacion',
                'ruta' => $documento->archivo_oficio_asignacion,
                'label' => 'Oficio de asignación',
                'folio' => $documento->oficio_asignacion ?: $documento->numero,
                'fecha' => $documento->fecha_asignacion,
                'nombre' => null,
            ],
            [
                'archivo' => 'general',
                'ruta' => $documento->archivo_path,
                'label' => 'Archivo principal',
                'folio' => $documento->numero,
                'fecha' => $documento->fecha_emision,
                'nombre' => $documento->archivo_nombre,
            ],
        ];

        $archivos = [];

        foreach ($candidatos as $candidato) {
            $ruta = str_replace('\\', '/', trim((string) ($candidato['ruta'] ?? '')));

            if ($ruta === '' || isset($rutasVistas[$ruta])) {
                continue;
            }

            $rutasVistas[$ruta] = true;
            $candidato['ruta'] = $ruta;
            $candidato['nombre'] = trim((string) ($candidato['nombre'] ?: basename($ruta)));
            $candidato['url'] = URL::temporarySignedRoute(
                'personal.documentos.signed',
                now()->addHours(24),
                [
                    'documento' => $documento->id,
                    'archivo' => $candidato['archivo'],
                ]
            );

            $archivos[] = $candidato;
        }

        return $archivos;
    }

    protected function lineaDocumentoPersonal(PersonalDocumento $documento, array $archivo): string
    {
        $tipo = trim((string) optional($documento->documentoTipo)->nombre);
        $tipo = $tipo !== '' ? $tipo : 'Documento';
        $label = trim((string) ($archivo['label'] ?? 'Archivo'));
        $folio = trim((string) ($archivo['folio'] ?? ''));
        $fecha = $archivo['fecha'] ?? null;

        $partes = [$tipo . ($label !== '' ? ' - ' . $label : '')];

        if ($folio !== '') {
            $partes[] = 'Folio ' . $folio;
        }

        if ($fecha) {
            $partes[] = 'Fecha ' . $fecha->format('d-m-Y');
        }

        if (!(bool) ($documento->activo ?? false)) {
            $partes[] = 'Inactivo';
        }

        $nombre = trim((string) ($archivo['nombre'] ?? ''));

        if ($nombre !== '') {
            $partes[] = $nombre;
        }

        return '- ' . implode(' | ', $partes) . "\n" . 'Descarga: ' . $archivo['url'];
    }

    protected function urlsTemporalesFotosPersonal(Personal $personal): array
    {
        try {
            $personal->loadMissing(['fotos', 'fotoPrincipal']);

            $fotos = collect();

            if ($personal->fotoPrincipal) {
                $fotos->push($personal->fotoPrincipal);
            }

            if ($personal->fotos) {
                $fotos = $fotos->merge($personal->fotos);
            }

            return $fotos
                ->filter(fn ($foto) => $foto && $foto->id && $foto->ruta)
                ->unique('id')
                ->take(3)
                ->map(fn ($foto) => URL::temporarySignedRoute(
                    'personal.fotos.signed',
                    now()->addMinutes(15),
                    ['foto' => $foto->id]
                ))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('WA personal temporary photo URL error', [
                'personal_id' => $personal->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function resolverLineaUnidad(Hechos $hecho): string
    {
        $unidadId = $hecho->unidad_org_id ? (int) $hecho->unidad_org_id : null;
        $unidadNombre = mb_strtoupper(trim((string) optional($hecho->unidadOrganizacional)->nombre), 'UTF-8');

        if (!$unidadId || $unidadId === 3) {
            return '';
        }

        switch ($unidadId) {
            case 1:
                return 'UNIDAD DE ATENCIÓN A SINIESTROS';
            case 2:
                return 'DELEGACIONES';
            case 4:
                return 'PROTECCIÓN A CARRETERAS';
            case 5:
                return 'PROTECCIÓN A VIALIDADES URBANAS';
            case 6:
                return 'FOMENTO A LA CULTURA VIAL';
            default:
                return $unidadNombre;
        }
    }

    protected function lineaUnidadPorId(?int $unidadId): string
    {
        if (!$unidadId || $unidadId === 3) {
            return '';
        }

        switch ((int) $unidadId) {
            case 1:
                return 'UNIDAD DE ATENCIÓN A SINIESTROS.';
            case 2:
                return 'DELEGACIONES.';
            case 4:
                return 'PROTECCIÓN A CARRETERAS.';
            case 5:
                return 'PROTECCIÓN A VIALIDADES URBANAS.';
            case 6:
                return 'FOMENTO A LA CULTURA VIAL.';
            default:
                return '';
        }
    }

    protected function buildVehiculoDescripcion($vehiculo): string
    {
        $partes = [];

        $partes[] = 'De la marca ' . $this->valorONoEspecificado($vehiculo->marca ?? null);
        $partes[] = 'tipo ' . $this->valorONoEspecificado($vehiculo->tipo ?? null);

        if (!empty($vehiculo->linea)) {
            $partes[] = 'línea ' . trim((string) $vehiculo->linea);
        }

        if (!empty($vehiculo->color)) {
            $partes[] = 'color ' . trim((string) $vehiculo->color);
        }

        if (!empty($vehiculo->placas)) {
            $partes[] = 'placas ' . trim((string) $vehiculo->placas);
        }

        if (!empty($vehiculo->serie)) {
            $partes[] = 'NIV ' . trim((string) $vehiculo->serie);
        }

        return implode(', ', $partes) . '.';
    }

    protected function buildVehiculoOcupantes($vehiculo): string
    {
        $nombre = $this->firstFilled([
            $vehiculo->nombre_conductor ?? null,
            $vehiculo->conductor_nombre ?? null,
            $vehiculo->nombre_persona ?? null,
            $vehiculo->responsable ?? null,
            $vehiculo->propietario ?? null,
        ]);

        $edad = $this->firstFilled([
            $vehiculo->edad_conductor ?? null,
            $vehiculo->conductor_edad ?? null,
            $vehiculo->edad_persona ?? null,
            $vehiculo->edad ?? null,
        ]);

        if ($nombre === '') {
            return '';
        }

        $texto = 'Manifiesta viajar a bordo el C. ' . $nombre;

        if ($edad !== '') {
            $texto .= ' de ' . $edad . ' años';
        }

        return $texto . '.';
    }

    protected function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function formatearHora(string $hora): string
    {
        if ($hora === '') {
            return '';
        }

        return substr($hora, 0, 5);
    }

    protected function valorONoEspecificado(?string $valor): string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : 'NO ESPECIFICADO';
    }

    protected function extraerUrlsDesdeCampo($valor): array
    {
        if (empty($valor)) {
            return [];
        }

        if (is_array($valor)) {
            return collect($valor)
                ->flatMap(fn ($item) => $this->extraerUrlsDesdeCampo($item))
                ->filter()
                ->values()
                ->all();
        }

        if (is_string($valor)) {
            $trim = trim($valor);

            if ($trim === '') {
                return [];
            }

            $json = json_decode($trim, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->extraerUrlsDesdeCampo($json);
            }

            if (str_contains($trim, ',')) {
                return collect(explode(',', $trim))
                    ->map(fn ($item) => $this->pathToUrl($item))
                    ->filter()
                    ->values()
                    ->all();
            }

            return array_filter([$this->pathToUrl($trim)]);
        }

        return [];
    }

    protected function pathToUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if ($this->startsWith($path, 'http://') || $this->startsWith($path, 'https://')) {
            return $path;
        }

        try {
            return url(Storage::url($path));
        } catch (\Throwable $e) {
            Log::warning('WA pathToUrl error', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
