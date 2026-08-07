<?php

namespace App\Services\Personal;

use App\Models\Personal;
use App\Models\Destacamento;
use App\Models\Unidad;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class PersonalExcelImportService
{
    private const MAX_FILAS = 5000;

    private $unidadCarreterasCache = [];

    private $destacamentosCache = [];

    private const ENCABEZADOS = [
        'nombre_completo' => ['NOMBRE COMPLETO'],
        'fecha_ingreso_unidad' => ['FECHA DE INGRESO AL GRUPO', 'FECHA INGRESO AL GRUPO'],
        'fecha_nacimiento' => ['FECHA DE NACIMIENTO'],
        'puesto' => ['CARGO Y/O FUNCIONES', 'CARGO Y O FUNCIONES', 'CARGO', 'FUNCIONES'],
        'tipo_sangre' => ['TIPO SANGUINEO', 'TIPO DE SANGRE'],
        'ultimo_grado_estudios' => ['ULTIMO GRADO DE ESTUDIOS', 'GRADO DE ESTUDIOS'],
        'numero_seguro_social' => ['NSS', 'NUMERO DE SEGURO SOCIAL'],
        'alergias' => ['ALERGIAS'],
        'rfc' => ['RFC'],
        'curp' => ['CURP'],
        'cuip' => ['CUIP'],
        'cup' => ['CUP'],
        'correo_electronico' => [
            'CORREO ELECTRONICO PERSONA',
            'CORREO ELECTRONICO PERSONAL',
            'CORREO ELECTRONICO',
        ],
        'destacamento' => [
            'DESTACAMENTO',
            'DESTACAMENTO DE ADSCRIPCION',
            'DESTACAMENTO ADSCRIPCION',
        ],
        'domicilio' => [
            'DOMICILIO',
            'DOMICILIO PARTICULAR',
            'DIRECCION',
            'DIRECCION PARTICULAR',
        ],
        'telefono_particular' => [
            'TELEFONO PARTICULAR',
            'TELEFONO PERSONAL',
            'NUMERO TELEFONICO',
            'NUMERO DE TELEFONO',
        ],
        'referencia_familiar_1' => [
            'REFERENCIA FAMILIAR 1',
            'CONTACTO DE EMERGENCIA 1',
        ],
        'referencia_familiar_2' => [
            'REFERENCIA FAMILIAR 2',
            'CONTACTO DE EMERGENCIA 2',
        ],
    ];

    public function importar(string $path, int $unidadId): array
    {
        $analisis = $this->analizarArchivo($path, $unidadId);
        $resultado = [
            'total' => count($analisis['registros']),
            'importados' => 0,
            'restaurados' => 0,
            'complementados' => 0,
            'omitidos' => 0,
            'contactos_importados' => 0,
            'emergencias_importadas' => 0,
            'domicilios_importados' => 0,
            'destacamentos_asignados' => 0,
            'errores' => [],
            'advertencias' => $analisis['advertencias'],
        ];
        $identificadoresArchivo = [];

        foreach ($analisis['registros'] as $registro) {
            $fila = $registro['fila'];
            $atributos = $registro['atributos'];
            $claveArchivo = $this->claveIdentificador($atributos);

            if ($claveArchivo !== null && isset($identificadoresArchivo[$claveArchivo])) {
                $resultado['omitidos']++;
                $resultado['errores'][] = "Fila {$fila}: registro duplicado dentro del archivo.";
                continue;
            }

            if ($claveArchivo !== null) {
                $identificadoresArchivo[$claveArchivo] = true;
            }

            try {
                $existente = $this->buscarExistente($atributos);

                if ($existente) {
                    if ($existente->trashed()) {
                        DB::transaction(function () use ($existente, $atributos) {
                            $existente->fill($atributos);
                            $existente->deleted_at = null;
                            $existente->save();
                        });

                        $resultado['restaurados']++;
                        $resultado['destacamentos_asignados'] += !empty($atributos['destacamento_id']) ? 1 : 0;
                        $relaciones = $this->guardarRelaciones(
                            $existente,
                            $registro,
                            $fila,
                            $resultado['advertencias']
                        );
                        $resultado['contactos_importados'] += $relaciones['contactos'];
                        $resultado['emergencias_importadas'] += $relaciones['emergencias'];
                        $resultado['domicilios_importados'] += $relaciones['domicilios'];
                        continue;
                    }

                    $relaciones = $this->guardarRelaciones(
                        $existente,
                        $registro,
                        $fila,
                        $resultado['advertencias']
                    );

                    $destacamentoAsignado = 0;

                    if (empty($existente->destacamento_id)
                        && !empty($atributos['destacamento_id'])
                        && (int) $existente->unidad_id === (int) $atributos['unidad_id']) {
                        $existente->update(['destacamento_id' => $atributos['destacamento_id']]);
                        $destacamentoAsignado = 1;
                        $resultado['destacamentos_asignados']++;
                    }

                    if (($relaciones['contactos'] + $relaciones['emergencias'] + $relaciones['domicilios'] + $destacamentoAsignado) > 0) {
                        $resultado['complementados']++;
                        $resultado['contactos_importados'] += $relaciones['contactos'];
                        $resultado['emergencias_importadas'] += $relaciones['emergencias'];
                        $resultado['domicilios_importados'] += $relaciones['domicilios'];
                    } else {
                        $resultado['omitidos']++;
                        $resultado['errores'][] = "Fila {$fila}: el personal ya está registrado; no se modificó su unidad.";
                    }

                    continue;
                }

                $personal = Personal::query()->create($atributos);
                $resultado['importados']++;
                $resultado['destacamentos_asignados'] += !empty($atributos['destacamento_id']) ? 1 : 0;
                $relaciones = $this->guardarRelaciones(
                    $personal,
                    $registro,
                    $fila,
                    $resultado['advertencias']
                );
                $resultado['contactos_importados'] += $relaciones['contactos'];
                $resultado['emergencias_importadas'] += $relaciones['emergencias'];
                $resultado['domicilios_importados'] += $relaciones['domicilios'];
            } catch (QueryException $e) {
                $resultado['omitidos']++;
                $resultado['errores'][] = "Fila {$fila}: no se pudo guardar por datos duplicados o inválidos.";
            }
        }

        return $resultado;
    }

    public function analizarArchivo(string $path, int $unidadId): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        try {
            $sheet = $this->hojaBaseDatos($spreadsheet->getWorksheetIterator());

            if (!$sheet) {
                throw new RuntimeException('El archivo no contiene la hoja BASE DE DATOS.');
            }

            [$filaEncabezados, $columnas] = $this->detectarEncabezados($sheet);
            $ultimaFila = $sheet->getHighestDataRow();

            if (($ultimaFila - $filaEncabezados) > self::MAX_FILAS) {
                throw new RuntimeException('El archivo supera el límite de ' . self::MAX_FILAS . ' filas.');
            }

            $registros = [];
            $advertencias = [];

            for ($fila = $filaEncabezados + 1; $fila <= $ultimaFila; $fila++) {
                $nombreCompleto = $this->texto($this->valor($sheet, $fila, $columnas['nombre_completo']));

                if ($nombreCompleto === null) {
                    continue;
                }

                $registros[] = array_merge([
                    'fila' => $fila,
                    'atributos' => $this->construirAtributos(
                        $sheet,
                        $fila,
                        $columnas,
                        $unidadId,
                        $advertencias
                    ),
                ], $this->construirRelaciones($sheet, $fila, $columnas, $advertencias));
            }

            if (empty($registros)) {
                throw new RuntimeException('No se encontraron filas de personal con nombre completo.');
            }

            return [
                'registros' => $registros,
                'advertencias' => $advertencias,
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    public function normalizarTipoSangre(?string $value): ?string
    {
        $normalizado = $this->normalizarTexto($value);

        if ($normalizado === '') {
            return null;
        }

        $compacto = str_replace([' ', '_'], '', $normalizado);
        $mapa = [
            'A+' => 'A_POSITIVO',
            'APOSITIVO' => 'A_POSITIVO',
            'A-' => 'A_NEGATIVO',
            'ANEGATIVO' => 'A_NEGATIVO',
            'B+' => 'B_POSITIVO',
            'BPOSITIVO' => 'B_POSITIVO',
            'B-' => 'B_NEGATIVO',
            'BNEGATIVO' => 'B_NEGATIVO',
            'AB+' => 'AB_POSITIVO',
            'ABPOSITIVO' => 'AB_POSITIVO',
            'AB-' => 'AB_NEGATIVO',
            'ABNEGATIVO' => 'AB_NEGATIVO',
            'O+' => 'O_POSITIVO',
            '0+' => 'O_POSITIVO',
            'OPOSITIVO' => 'O_POSITIVO',
            '0POSITIVO' => 'O_POSITIVO',
            'O-' => 'O_NEGATIVO',
            '0-' => 'O_NEGATIVO',
            'ONEGATIVO' => 'O_NEGATIVO',
            '0NEGATIVO' => 'O_NEGATIVO',
            'DESCONOCIDO' => 'DESCONOCIDO',
            'NOSABE' => 'DESCONOCIDO',
        ];

        return $mapa[$compacto] ?? null;
    }

    public function normalizarGradoEstudios(?string $value): ?string
    {
        $value = $this->normalizarTexto($value);

        foreach ([
            'DOCTOR' => 'DOCTORADO',
            'MAESTR' => 'MAESTRIA',
            'ESPECIAL' => 'ESPECIALIDAD',
            'LICENCIAT' => 'LICENCIATURA',
            'INGENIER' => 'LICENCIATURA',
            'UNIVERSIT' => 'LICENCIATURA',
            'TECNIC' => 'CARRERA_TECNICA',
            'BACHILL' => 'BACHILLERATO',
            'PREPARATOR' => 'BACHILLERATO',
            'MEDIA SUPERIOR' => 'BACHILLERATO',
            'SECUNDAR' => 'SECUNDARIA',
            'PRIMAR' => 'PRIMARIA',
            'SIN ESTUD' => 'SIN_ESTUDIOS',
        ] as $fragmento => $resultado) {
            if (Str::contains($value, $fragmento)) {
                return $resultado;
            }
        }

        return null;
    }

    public function separarNombre(string $nombreCompleto): array
    {
        $partes = preg_split('/\s+/u', trim($nombreCompleto)) ?: [];

        if (count($partes) >= 3) {
            return [
                'nombre' => implode(' ', array_slice($partes, 2)),
                'ap_paterno' => $partes[0],
                'ap_materno' => $partes[1],
            ];
        }

        if (count($partes) === 2) {
            return [
                'nombre' => $partes[1],
                'ap_paterno' => $partes[0],
                'ap_materno' => null,
            ];
        }

        return [
            'nombre' => $partes[0] ?? '',
            'ap_paterno' => null,
            'ap_materno' => null,
        ];
    }

    private function construirAtributos(
        Worksheet $sheet,
        int $fila,
        array $columnas,
        int $unidadId,
        array &$advertencias
    ): array {
        $nombreCompleto = Str::upper($this->texto($this->valor($sheet, $fila, $columnas['nombre_completo'])) ?? '');
        $nombre = $this->separarNombre($nombreCompleto);
        $puesto = $this->textoCampo($sheet, $fila, $columnas, 'puesto', 120);
        $tipoSangreRaw = $this->textoCampo($sheet, $fila, $columnas, 'tipo_sangre', 80);
        $tipoSangre = $this->normalizarTipoSangre($tipoSangreRaw);
        $estudiosRaw = $this->textoCampo($sheet, $fila, $columnas, 'ultimo_grado_estudios', 120);
        $estudios = $this->normalizarGradoEstudios($estudiosRaw);
        $correo = Str::lower($this->textoCampo($sheet, $fila, $columnas, 'correo_electronico', 255) ?? '');
        $fechaIngresoRaw = $this->valorCampo($sheet, $fila, $columnas, 'fecha_ingreso_unidad');
        $fechaNacimientoRaw = $this->valorCampo($sheet, $fila, $columnas, 'fecha_nacimiento');

        if ($tipoSangreRaw && !$tipoSangre) {
            $advertencias[] = "Fila {$fila}: tipo sanguíneo '{$tipoSangreRaw}' no reconocido; se dejó vacío.";
        }

        if ($estudiosRaw && !$estudios) {
            $advertencias[] = "Fila {$fila}: grado de estudios '{$estudiosRaw}' no reconocido; se dejó vacío.";
        }

        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $advertencias[] = "Fila {$fila}: correo electrónico inválido; se dejó vacío.";
            $correo = '';
        }

        $fechaIngreso = $this->normalizarFecha($fechaIngresoRaw);
        $fechaNacimiento = $this->normalizarFecha($fechaNacimientoRaw);

        if ($fechaIngresoRaw !== null && !$fechaIngreso) {
            $advertencias[] = "Fila {$fila}: fecha de ingreso inválida; se dejó vacía.";
        }

        if ($fechaNacimientoRaw !== null && !$fechaNacimiento) {
            $advertencias[] = "Fila {$fila}: fecha de nacimiento inválida; se dejó vacía.";
        }

        [$alergiasEstado, $alergias] = $this->normalizarAlergias(
            $this->textoCampo($sheet, $fila, $columnas, 'alergias', 2000)
        );

        return array_merge($nombre, [
            'unidad_id' => $unidadId,
            'puesto' => $puesto ? Str::upper($puesto) : null,
            'categoria' => Str::contains($this->normalizarTexto($puesto), 'ADMINISTRAT')
                ? 'ADMINISTRATIVO'
                : 'OPERATIVO',
            'estatus' => 'ACTIVO',
            'fecha_ingreso_unidad' => $fechaIngreso,
            'fecha_nacimiento' => $fechaNacimiento,
            'tipo_sangre' => $tipoSangre,
            'ultimo_grado_estudios' => $estudios,
            'numero_seguro_social' => $this->identificador($this->valorCampo($sheet, $fila, $columnas, 'numero_seguro_social'), 20),
            'alergias_estado' => $alergiasEstado,
            'alergias' => $alergias,
            'rfc' => $this->identificador($this->valorCampo($sheet, $fila, $columnas, 'rfc'), 13),
            'curp' => $this->identificador($this->valorCampo($sheet, $fila, $columnas, 'curp'), 18),
            'cuip' => $this->identificador($this->valorCampo($sheet, $fila, $columnas, 'cuip'), 30),
            'cup' => $this->identificador($this->valorCampo($sheet, $fila, $columnas, 'cup'), 100),
            'correo_electronico' => $correo !== '' ? $correo : null,
            'destacamento_id' => $this->resolverDestacamentoId(
                $this->textoCampo($sheet, $fila, $columnas, 'destacamento', 160),
                $unidadId,
                $fila,
                $advertencias
            ),
        ]);
    }

    private function resolverDestacamentoId(
        ?string $value,
        int $unidadId,
        int $fila,
        array &$advertencias
    ): ?int {
        if (!$value || !$this->esUnidadCarreteras($unidadId)) {
            return null;
        }

        if (!isset($this->destacamentosCache[$unidadId])) {
            $this->destacamentosCache[$unidadId] = Destacamento::query()
                ->where('unidad_id', $unidadId)
                ->where('activo', 1)
                ->get(['id', 'clave', 'nombre']);
        }

        $buscado = $this->normalizarTexto($value);
        $sinPrefijo = trim(preg_replace('/^DESTACAMENTO\s+/', '', $buscado) ?? $buscado);

        foreach ($this->destacamentosCache[$unidadId] as $destacamento) {
            $nombre = $this->normalizarTexto($destacamento->nombre);
            $clave = $this->normalizarTexto($destacamento->clave);
            $claveNumerica = ctype_digit($clave) ? (string) ((int) $clave) : $clave;
            $candidatos = array_filter([
                $nombre,
                $clave,
                $claveNumerica,
                trim($clave . ' ' . $nombre),
                trim($claveNumerica . ' ' . $nombre),
                trim($nombre . ' ' . $clave),
                trim($nombre . ' ' . $claveNumerica),
            ]);

            if (in_array($buscado, $candidatos, true) || in_array($sinPrefijo, $candidatos, true)) {
                return (int) $destacamento->id;
            }
        }

        $advertencias[] = "Fila {$fila}: destacamento '{$value}' no reconocido para Protección a Carreteras; se dejó vacío.";

        return null;
    }

    private function esUnidadCarreteras(int $unidadId): bool
    {
        if (!array_key_exists($unidadId, $this->unidadCarreterasCache)) {
            $this->unidadCarreterasCache[$unidadId] = Unidad::query()
                ->whereKey($unidadId)
                ->where('slug', 'carreteras')
                ->exists();
        }

        return $this->unidadCarreterasCache[$unidadId];
    }

    private function construirRelaciones(
        Worksheet $sheet,
        int $fila,
        array $columnas,
        array &$advertencias
    ): array {
        $contactos = [];
        $emergencias = [];
        $domicilios = [];
        $telefonoRaw = $this->textoCampo($sheet, $fila, $columnas, 'telefono_particular', 80);
        $telefono = $this->normalizarTelefono($telefonoRaw);

        if ($telefonoRaw && !$telefono) {
            $advertencias[] = "Fila {$fila}: teléfono particular '{$telefonoRaw}' no reconocido; se dejó vacío.";
        }

        if ($telefono) {
            $contactos[] = [
                'tipo' => 'TELEFONO_PERSONAL',
                'valor' => $telefono,
                'telefono_personal' => $telefono,
                'es_principal' => true,
            ];
        }

        foreach (['referencia_familiar_1', 'referencia_familiar_2'] as $campo) {
            $referenciaRaw = $this->textoCampo($sheet, $fila, $columnas, $campo, 500);

            if (!$referenciaRaw) {
                continue;
            }

            $referencia = $this->separarReferenciaFamiliar($referenciaRaw);

            if (!$referencia) {
                $advertencias[] = "Fila {$fila}: referencia familiar '{$referenciaRaw}' incompleta; debe incluir nombre y teléfono.";
                continue;
            }

            $emergencias[] = $referencia;
        }

        $domicilioRaw = $this->textoCampo($sheet, $fila, $columnas, 'domicilio', 1000);

        if ($domicilioRaw) {
            $domicilios[] = $this->construirDomicilio($domicilioRaw);
        }

        return compact('contactos', 'emergencias', 'domicilios');
    }

    private function construirDomicilio(string $value): array
    {
        $texto = Str::upper($this->texto($value) ?? '');
        $segmentos = array_values(array_filter(array_map('trim', preg_split('/[,;]+/u', $texto) ?: [])));
        $calle = $segmentos[0] ?? $texto;
        $municipio = null;
        $estado = null;
        $colonia = null;
        $cp = null;

        if (preg_match('/\bMUNICIPIO\s+(?:DE\s+)?(.+?)(?=\s+(?:EDO|ESTADO)\b|$)/u', $texto, $coincidencia)) {
            $municipio = trim($coincidencia[1], " \t\n\r\0\x0B,.;");
        }

        if (preg_match('/\b(?:EDO|ESTADO)\.?\s*(?:DE\s+)?(.+?)(?=\s+(?:C\.?P\.?|CODIGO POSTAL)\b|$)/u', $texto, $coincidencia)) {
            $estado = trim($coincidencia[1], " \t\n\r\0\x0B,.;");
        }

        if (preg_match('/\bCOL(?:ONIA)?\.?\s+(.+?)(?=\s+MUNICIPIO\b|[,;]|$)/u', $texto, $coincidencia)) {
            $colonia = trim($coincidencia[1], " \t\n\r\0\x0B,.;");
        }

        if (preg_match('/\b(?:C\.?P\.?|CODIGO POSTAL)\s*:?-?\s*(\d{5})\b/u', $texto, $coincidencia)) {
            $cp = $coincidencia[1];
        }

        return [
            'calle' => Str::substr($calle !== '' ? $calle : 'NO ESPECIFICADA', 0, 191),
            'numero_ext' => 'S/N',
            'numero_int' => null,
            'colonia' => Str::substr($colonia ?: 'NO ESPECIFICADA', 0, 191),
            'municipio' => Str::substr($municipio ?: 'NO ESPECIFICADO', 0, 191),
            'estado' => Str::substr($estado ?: 'NO ESPECIFICADO', 0, 191),
            'cp' => $cp ?: 'S/C',
            'referencias' => Str::substr($texto, 0, 255),
            'es_actual' => true,
        ];
    }

    private function separarReferenciaFamiliar(string $value): ?array
    {
        $telefono = $this->extraerTelefono($value);

        if (!$telefono) {
            return null;
        }

        $parentesco = null;

        if (preg_match('/\(([^()]+)\)/u', $value, $coincidencia)) {
            $parentesco = Str::upper(Str::substr(trim($coincidencia[1]), 0, 80));
        }

        $nombre = preg_replace('/\([^()]+\)/u', ' ', $value) ?? $value;
        $nombre = preg_replace('/(?:\+?\d[\d\s.\-]{5,}\d)/u', ' ', $nombre) ?? $nombre;
        $nombre = Str::upper(Str::substr(trim(preg_replace('/\s+/u', ' ', $nombre) ?? ''), 0, 191));

        if ($nombre === '') {
            return null;
        }

        return [
            'nombre' => $nombre,
            'nombre_contacto' => $nombre,
            'parentesco' => $parentesco,
            'telefono' => $telefono,
            'telefono_emergencia' => $telefono,
        ];
    }

    private function extraerTelefono(string $value): ?string
    {
        if (!preg_match_all('/(?:\+?\d[\d\s.\-]{5,}\d)/u', $value, $coincidencias)) {
            return null;
        }

        foreach (array_reverse($coincidencias[0]) as $candidato) {
            $telefono = $this->normalizarTelefono($candidato);

            if ($telefono) {
                return $telefono;
            }
        }

        return null;
    }

    private function normalizarTelefono(?string $value): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $value) ?? '';
        $longitud = strlen($digitos);

        return $longitud >= 7 && $longitud <= 15 ? $digitos : null;
    }

    private function guardarRelaciones(
        Personal $personal,
        array $registro,
        int $fila,
        array &$advertencias
    ): array
    {
        $guardados = ['contactos' => 0, 'emergencias' => 0, 'domicilios' => 0];

        foreach ($registro['contactos'] ?? [] as $contacto) {
            if ($this->contactoExiste($personal, $contacto['valor'])) {
                continue;
            }

            try {
                DB::transaction(function () use ($personal, $contacto) {
                    if (!empty($contacto['es_principal'])) {
                        $personal->contactos()->update(['es_principal' => false]);
                    }

                    $personal->contactos()->create($contacto);
                });
                $guardados['contactos']++;
            } catch (QueryException $e) {
                $advertencias[] = "Fila {$fila}: el personal se importó, pero no se pudo guardar su teléfono particular.";
                Log::warning('No se pudo guardar teléfono durante la importación de personal.', [
                    'fila' => $fila,
                    'personal_id' => $personal->id,
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($registro['emergencias'] ?? [] as $indice => $emergencia) {
            if ($this->emergenciaExiste($personal, $emergencia)) {
                continue;
            }

            try {
                $personal->emergencias()->create($emergencia);
                $guardados['emergencias']++;
            } catch (QueryException $e) {
                $numero = $indice + 1;
                $advertencias[] = "Fila {$fila}: el personal se importó, pero no se pudo guardar la referencia familiar {$numero}.";
                Log::warning('No se pudo guardar referencia familiar durante la importación de personal.', [
                    'fila' => $fila,
                    'referencia' => $numero,
                    'personal_id' => $personal->id,
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($registro['domicilios'] ?? [] as $domicilio) {
            if ($this->domicilioExiste($personal, $domicilio)) {
                continue;
            }

            try {
                DB::transaction(function () use ($personal, $domicilio) {
                    $personal->domicilios()->update(['es_actual' => false]);
                    $personal->domicilios()->create($domicilio);
                });
                $guardados['domicilios']++;
            } catch (QueryException $e) {
                $advertencias[] = "Fila {$fila}: el personal se importó, pero no se pudo guardar su domicilio.";
                Log::warning('No se pudo guardar domicilio durante la importación de personal.', [
                    'fila' => $fila,
                    'personal_id' => $personal->id,
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $guardados;
    }

    private function contactoExiste(Personal $personal, string $telefono): bool
    {
        $telefono = $this->normalizarTelefono($telefono);

        return $personal->contactos()
            ->get(['valor', 'telefono_personal', 'telefono_secundario'])
            ->contains(function ($contacto) use ($telefono) {
                foreach (['valor', 'telefono_personal', 'telefono_secundario'] as $campo) {
                    if ($telefono && $this->normalizarTelefono($contacto->{$campo}) === $telefono) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function emergenciaExiste(Personal $personal, array $emergencia): bool
    {
        $telefono = $this->normalizarTelefono($emergencia['telefono_emergencia'] ?? null);
        $nombre = $this->normalizarTexto($emergencia['nombre_contacto'] ?? null);

        return $personal->emergencias()
            ->get(['nombre', 'nombre_contacto', 'telefono', 'telefono_emergencia'])
            ->contains(function ($existente) use ($telefono, $nombre) {
                $telefonoExistente = $this->normalizarTelefono(
                    $existente->telefono_emergencia ?? $existente->telefono
                );
                $nombreExistente = $this->normalizarTexto(
                    $existente->nombre_contacto ?? $existente->nombre
                );

                return $telefonoExistente === $telefono && $nombreExistente === $nombre;
            });
    }

    private function domicilioExiste(Personal $personal, array $domicilio): bool
    {
        $referencia = $this->normalizarTexto($domicilio['referencias'] ?? null);

        return $personal->domicilios()
            ->get(['calle', 'numero_ext', 'colonia', 'municipio', 'estado', 'cp', 'referencias'])
            ->contains(function ($existente) use ($referencia, $domicilio) {
                if ($referencia !== ''
                    && $this->normalizarTexto($existente->referencias) === $referencia) {
                    return true;
                }

                foreach (['calle', 'numero_ext', 'colonia', 'municipio', 'estado', 'cp'] as $campo) {
                    if ($this->normalizarTexto($existente->{$campo})
                        !== $this->normalizarTexto($domicilio[$campo] ?? null)) {
                        return false;
                    }
                }

                return true;
            });
    }

    private function detectarEncabezados(Worksheet $sheet): array
    {
        $ultimaColumna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $limiteFila = min(50, $sheet->getHighestDataRow());

        for ($fila = 1; $fila <= $limiteFila; $fila++) {
            $encontrados = [];

            for ($columna = 1; $columna <= $ultimaColumna; $columna++) {
                $encabezado = $this->normalizarTexto($this->texto($sheet->getCellByColumnAndRow($columna, $fila)->getValue()));

                if ($encabezado !== '') {
                    $encontrados[$encabezado] = $columna;
                }
            }

            $columnas = [];

            foreach (self::ENCABEZADOS as $campo => $aliases) {
                foreach ($aliases as $alias) {
                    if (isset($encontrados[$alias])) {
                        $columnas[$campo] = $encontrados[$alias];
                        break;
                    }
                }
            }

            $columnas = $this->detectarReferenciasFamiliaresAgrupadas(
                $sheet,
                $fila,
                $encontrados,
                $columnas
            );

            if (isset($columnas['nombre_completo'])) {
                return [$fila, $columnas];
            }
        }

        throw new RuntimeException('No se encontró la columna NOMBRE COMPLETO en las primeras 50 filas.');
    }

    private function detectarReferenciasFamiliaresAgrupadas(
        Worksheet $sheet,
        int $fila,
        array $encontrados,
        array $columnas
    ): array {
        $columnaGrupo = $encontrados['REFERENCIAS FAMILIARES']
            ?? $encontrados['CONTACTOS DE EMERGENCIA']
            ?? $encontrados['CONTACTO DE EMERGENCIA']
            ?? null;

        if (!$columnaGrupo) {
            return $columnas;
        }

        $columnasGrupo = [$columnaGrupo];

        foreach ($sheet->getMergeCells() as $rango) {
            [$inicio, $fin] = Coordinate::rangeBoundaries($rango);

            if ($fila >= $inicio[1] && $fila <= $fin[1]
                && $columnaGrupo >= $inicio[0] && $columnaGrupo <= $fin[0]) {
                $columnasGrupo = range($inicio[0], $fin[0]);
                break;
            }
        }

        if (count($columnasGrupo) === 1) {
            $siguiente = $this->normalizarTexto(
                $this->texto($sheet->getCellByColumnAndRow($columnaGrupo + 1, $fila + 1)->getValue())
            );

            if ($siguiente === '2') {
                $columnasGrupo[] = $columnaGrupo + 1;
            }
        }

        foreach (array_slice($columnasGrupo, 0, 2) as $indice => $columna) {
            $columnas['referencia_familiar_' . ($indice + 1)] = $columna;
        }

        return $columnas;
    }

    private function hojaBaseDatos(iterable $hojas): ?Worksheet
    {
        $primeraConEncabezado = null;

        foreach ($hojas as $hoja) {
            if ($this->normalizarTexto($hoja->getTitle()) === 'BASE DE DATOS') {
                return $hoja;
            }

            $primeraConEncabezado = $primeraConEncabezado ?: $hoja;
        }

        return $primeraConEncabezado;
    }

    private function valor(Worksheet $sheet, int $fila, int $columna)
    {
        return $sheet->getCellByColumnAndRow($columna, $fila)->getValue();
    }

    private function valorCampo(Worksheet $sheet, int $fila, array $columnas, string $campo)
    {
        return isset($columnas[$campo]) ? $this->valor($sheet, $fila, $columnas[$campo]) : null;
    }

    private function textoCampo(Worksheet $sheet, int $fila, array $columnas, string $campo, int $max): ?string
    {
        $texto = $this->texto($this->valorCampo($sheet, $fila, $columnas, $campo));

        return $texto === null ? null : Str::substr($texto, 0, $max);
    }

    private function texto($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            $value = floor((float) $value) == (float) $value
                ? number_format((float) $value, 0, '.', '')
                : (string) $value;
        }

        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $value === '' ? null : $value;
    }

    private function identificador($value, int $max): ?string
    {
        $value = $this->texto($value);

        return $value === null ? null : Str::upper(Str::substr($value, 0, $max));
    }

    private function normalizarTexto(?string $value): string
    {
        $value = Str::upper(Str::ascii(trim((string) $value)));

        return trim(preg_replace('/[^A-Z0-9+\-\/]+/', ' ', $value) ?? '');
    }

    private function normalizarFecha($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value > 0 && (float) $value < 100000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        $value = $this->texto($value);

        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'm/d/Y', 'm/d/y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat('!' . $formato, $value);

                if ($fecha !== false && $fecha->format($formato) === $value) {
                    return $fecha->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Probar el siguiente formato.
            }
        }

        return null;
    }

    private function normalizarAlergias(?string $value): array
    {
        $normalizado = $this->normalizarTexto($value);

        if ($normalizado === '') {
            return [null, null];
        }

        if (in_array($normalizado, ['NO', 'NINGUNA', 'NINGUNO', 'N/A', 'NA', 'NO APLICA'], true)) {
            return ['NINGUNA', null];
        }

        if (Str::contains($normalizado, ['DESCONO', 'NO SABE'])) {
            return ['DESCONOCIDAS', null];
        }

        return ['SI', $value];
    }

    private function buscarExistente(array $atributos): ?Personal
    {
        $identificadores = array_filter([
            'curp' => $atributos['curp'] ?? null,
            'cuip' => $atributos['cuip'] ?? null,
            'cup' => $atributos['cup'] ?? null,
            'numero_seguro_social' => $atributos['numero_seguro_social'] ?? null,
        ]);

        if (!empty($identificadores)) {
            return Personal::query()
                ->withTrashed()
                ->where(function ($query) use ($identificadores) {
                    foreach ($identificadores as $campo => $valor) {
                        $query->orWhere($campo, $valor);
                    }
                })
                ->first();
        }

        return Personal::query()
            ->withTrashed()
            ->where('unidad_id', $atributos['unidad_id'])
            ->where('nombre', $atributos['nombre'])
            ->where('ap_paterno', $atributos['ap_paterno'])
            ->where('ap_materno', $atributos['ap_materno'])
            ->when($atributos['fecha_nacimiento'] ?? null, function ($query, $fecha) {
                $query->whereDate('fecha_nacimiento', $fecha);
            })
            ->first();
    }

    private function claveIdentificador(array $atributos): ?string
    {
        foreach (['curp', 'cuip', 'cup', 'numero_seguro_social'] as $campo) {
            if (!empty($atributos[$campo])) {
                return $campo . ':' . $atributos[$campo];
            }
        }

        return null;
    }
}
