<?php

namespace App\Services\Personal;

use App\Models\Personal;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class PersonalExcelImportService
{
    private const MAX_FILAS = 5000;

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
    ];

    public function importar(string $path, int $unidadId): array
    {
        $analisis = $this->analizarArchivo($path, $unidadId);
        $resultado = [
            'total' => count($analisis['registros']),
            'importados' => 0,
            'omitidos' => 0,
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

            if ($this->yaExiste($atributos)) {
                $resultado['omitidos']++;
                $resultado['errores'][] = "Fila {$fila}: el personal ya está registrado; no se modificó su unidad.";
                continue;
            }

            try {
                Personal::query()->create($atributos);
                $resultado['importados']++;
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

                $registros[] = [
                    'fila' => $fila,
                    'atributos' => $this->construirAtributos(
                        $sheet,
                        $fila,
                        $columnas,
                        $unidadId,
                        $advertencias
                    ),
                ];
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
        ]);
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

            if (isset($columnas['nombre_completo'])) {
                return [$fila, $columnas];
            }
        }

        throw new RuntimeException('No se encontró la columna NOMBRE COMPLETO en las primeras 50 filas.');
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

    private function yaExiste(array $atributos): bool
    {
        $identificadores = array_filter([
            'curp' => $atributos['curp'] ?? null,
            'cuip' => $atributos['cuip'] ?? null,
            'cup' => $atributos['cup'] ?? null,
            'numero_seguro_social' => $atributos['numero_seguro_social'] ?? null,
        ]);

        if (!empty($identificadores)) {
            return Personal::query()
                ->where(function ($query) use ($identificadores) {
                    foreach ($identificadores as $campo => $valor) {
                        $query->orWhere($campo, $valor);
                    }
                })
                ->exists();
        }

        return Personal::query()
            ->where('unidad_id', $atributos['unidad_id'])
            ->where('nombre', $atributos['nombre'])
            ->where('ap_paterno', $atributos['ap_paterno'])
            ->where('ap_materno', $atributos['ap_materno'])
            ->when($atributos['fecha_nacimiento'] ?? null, function ($query, $fecha) {
                $query->whereDate('fecha_nacimiento', $fecha);
            })
            ->exists();
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
