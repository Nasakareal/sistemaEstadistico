@php
    $hechoIph = $mapeo['hecho'] ?? [];
    $puesta = $mapeo['puesta_disposicion'] ?? null;
    $vehiculosHecho = $mapeo['vehiculos_hecho'] ?? [];
    $conductoresHecho = $mapeo['conductores_hecho'] ?? [];
    $personasPuesta = $mapeo['personas'] ?? [];
    $vehiculosPuesta = $mapeo['vehiculos'] ?? [];
    $objetosPuesta = $mapeo['objetos'] ?? [];
    $anexos = $mapeo['anexos'] ?? [];
    $lesionadosHecho = $mapeo['lesionados_hecho'] ?? [];

    $valor = function ($valor, string $default = 'No especificado') {
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_null($valor) || trim((string) $valor) === '') {
            return $default;
        }

        return (string) $valor;
    };

    $ubicacion = $hechoIph['ubicacion'] ?? [];
    $lugarHecho = collect([
        $ubicacion['calle'] ?? null,
        $ubicacion['colonia'] ?? null,
        $ubicacion['municipio'] ?? null,
    ])->filter()->implode(', ');

    $sspHorizontal = file_exists(public_path('img/ssp_horizontal.png'))
        ? asset('img/ssp_horizontal.png')
        : asset('img/SSP_horizontal.png');
    $mexicoLogo = asset('img/mexico.png');

    $unidadHechoId = (int) ($hechoIph['unidad_org_id'] ?? 0);
    $oficinaEncabezado = $unidadHechoId === 2
        ? 'Unidad de Delegaciones'
        : 'Unidad de Atención a Siniestros';

    $oficioEncabezado = $puesta['oficio'] ?? ($hechoIph['oficio_mp'] ?? null);
    $expedienteEncabezado = $puesta['carpeta_investigacion'] ?? null;
    $municipioEncabezadoRaw = trim((string) ($ubicacion['municipio'] ?? ''));
    $municipioEncabezado = $municipioEncabezadoRaw !== ''
        ? mb_convert_case(mb_strtolower($municipioEncabezadoRaw, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
        : 'Morelia';
    $fechaEncabezado = now('America/Mexico_City')->format('d-m-Y');
    $nombreCreadorHecho = mb_strtoupper($valor($hechoIph['creador_nombre'] ?? null, (string) ($hechoIph['perito'] ?? '')), 'UTF-8');
    $croquisPreview = trim((string) ($anexos['croquis_preview'] ?? ''));
    $croquisPreviewUrl = null;

    if ($croquisPreview !== '') {
        $croquisPreviewUrl = preg_match('/^(data:image|https?:\/\/)/i', $croquisPreview)
            ? $croquisPreview
            : asset(ltrim($croquisPreview, '/'));
    }

    $imageUrl = function ($path): ?string {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('/^(data:image|https?:\/\/)/i', $path)) {
            return $path;
        }

        $cleanPath = ltrim($path, '/\\');

        if (strpos($cleanPath, 'storage/') === 0) {
            return asset($cleanPath);
        }

        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    };

    $fijacionFotos = collect();

    if ($fotoLugarUrl = $imageUrl($anexos['foto_lugar'] ?? null)) {
        $fijacionFotos->push([
            'src' => $fotoLugarUrl,
            'alt' => 'Foto del lugar del hecho',
        ]);
    }

    $fechaPuestaIph = trim((string) ($puesta['fecha_puesta'] ?? ''));
    $horaPuestaIph = trim((string) ($puesta['hora_puesta'] ?? ''));

    if ($fechaPuestaIph === '') {
        $fechaPuestaIph = now('America/Mexico_City')->format('Y-m-d');
    }

    if ($horaPuestaIph === '') {
        $horaPuestaIph = now('America/Mexico_City')->format('H:i');
    }

    $fechaPuestaPartes = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fechaPuestaIph, $fechaMatches)
        ? ['dia' => $fechaMatches[3], 'mes' => $fechaMatches[2], 'anio' => $fechaMatches[1]]
        : ['dia' => now('America/Mexico_City')->format('d'), 'mes' => now('America/Mexico_City')->format('m'), 'anio' => now('America/Mexico_City')->format('Y')];
    $horaPuestaPartes = ['hora' => '', 'minuto' => ''];
    $referenciaIph = [
        ['label' => 'EDO', 'value' => '16', 'length' => 2, 'cream' => true],
        ['label' => 'INST', 'value' => 'PE', 'length' => 2, 'cream' => false],
        ['label' => 'GOB', 'value' => '010', 'length' => 3, 'cream' => true],
        ['label' => 'MPIO', 'value' => '000', 'length' => 3, 'cream' => false],
        ['label' => 'DD', 'value' => $fechaPuestaPartes['dia'], 'length' => 2, 'cream' => true],
        ['label' => 'MM', 'value' => $fechaPuestaPartes['mes'], 'length' => 2, 'cream' => false],
        ['label' => 'AAAA', 'value' => $fechaPuestaPartes['anio'], 'length' => 4, 'cream' => true],
        ['label' => 'HH', 'value' => '', 'length' => 2, 'cream' => false],
        ['label' => 'MM', 'value' => '', 'length' => 2, 'cream' => true],
    ];
    $expedienteIph = preg_replace('/\s+/', '', (string) ($expedienteEncabezado ?? '')) ?: '';
    $vehiculosAnexoCantidad = str_pad((string) count($vehiculosHecho), 3, '0', STR_PAD_LEFT);

    $separarNombreIph = function (?string $nombre): array {
        $partes = preg_split('/\s+/', trim((string) $nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($partes) >= 3) {
            return [
                'primer_apellido' => mb_strtoupper(array_shift($partes), 'UTF-8'),
                'segundo_apellido' => mb_strtoupper(array_shift($partes), 'UTF-8'),
                'nombres' => mb_strtoupper(implode(' ', $partes), 'UTF-8'),
            ];
        }

        return [
            'primer_apellido' => '',
            'segundo_apellido' => '',
            'nombres' => mb_strtoupper(trim((string) $nombre), 'UTF-8'),
        ];
    };

    $nombreQuienPoneIph = $valor($puesta['nombre_policia'] ?? ($hechoIph['creador_nombre'] ?? ($hechoIph['perito'] ?? null)), '');
    $nombreQuienPonePartes = $separarNombreIph($nombreQuienPoneIph);
    $adscripcionQuienPoneIph = mb_strtoupper($valor($hechoIph['unidad_org_nombre'] ?? $oficinaEncabezado, ''), 'UTF-8');
    $cargoQuienPoneIph = 'POLICIA';
    $nombreAutoridadIph = $valor($puesta['nombre_mp'] ?? ($puesta['autoridad_receptora'] ?? null), '');
    $nombreAutoridadPartes = $separarNombreIph($nombreAutoridadIph);
    $fiscaliaAutoridadIph = mb_strtoupper($valor($puesta['autoridad_receptora'] ?? 'FISCALÍA GENERAL DEL ESTADO', ''), 'UTF-8');
    $cargoAutoridadIph = mb_strtoupper($valor($puesta['area'] ?? '', ''), 'UTF-8');
    $fechaHechoIph = trim((string) ($hechoIph['fecha'] ?? ''));
    $horaHechoIph = trim((string) ($hechoIph['hora'] ?? ''));
    $fechaHechoPartes = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fechaHechoIph, $fechaHechoMatches)
        ? ['dia' => $fechaHechoMatches[3], 'mes' => $fechaHechoMatches[2], 'anio' => $fechaHechoMatches[1]]
        : ['dia' => '', 'mes' => '', 'anio' => ''];
    $horaHechoPartes = preg_match('/^(\d{2}):(\d{2})/', $horaHechoIph, $horaHechoMatches)
        ? ['hora' => $horaHechoMatches[1], 'minuto' => $horaHechoMatches[2]]
        : ['hora' => '', 'minuto' => ''];
    $arriboPartes = ['dia' => $fechaHechoPartes['dia'], 'mes' => $fechaHechoPartes['mes'], 'anio' => $fechaHechoPartes['anio'], 'hora' => '', 'minuto' => ''];

    if ($fechaHechoIph !== '' && $horaHechoIph !== '') {
        try {
            $arriboCarbon = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $fechaHechoIph . ' ' . substr($horaHechoIph, 0, 5), 'America/Mexico_City')
                ->addMinutes(30);
            $arriboPartes = [
                'dia' => $arriboCarbon->format('d'),
                'mes' => $arriboCarbon->format('m'),
                'anio' => $arriboCarbon->format('Y'),
                'hora' => $arriboCarbon->format('H'),
                'minuto' => $arriboCarbon->format('i'),
            ];
        } catch (\Throwable $e) {
            $arriboPartes = ['dia' => $fechaHechoPartes['dia'], 'mes' => $fechaHechoPartes['mes'], 'anio' => $fechaHechoPartes['anio'], 'hora' => '', 'minuto' => ''];
        }
    }

    $folioC5Iph = preg_replace('/\s+/', '', (string) ($hechoIph['folio_c5i'] ?? '')) ?: '';
    $tieneFolioC5 = $folioC5Iph !== '';
    $codigoPostalIph = '';

    if (preg_match('/\b(\d{5})\b/', (string) ($ubicacion['ubicacion_formateada'] ?? ''), $cpMatches)) {
        $codigoPostalIph = $cpMatches[1];
    }

    $latitudIph = preg_replace('/[^0-9]/', '', (string) ($ubicacion['lat'] ?? '')) ?: '';
    $longitudIph = preg_replace('/[^0-9]/', '', (string) ($ubicacion['lng'] ?? '')) ?: '';

    $renderIphBoxes = function ($valor, int $length, string $extraClass = '') {
        $texto = preg_replace('/\s+/', '', (string) $valor) ?? '';
        $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $html = '';

        for ($i = 0; $i < $length; $i++) {
            $html .= '<span class="iph-box ' . e($extraClass) . '">' . e($chars[$i] ?? '') . '</span>';
        }

        return $html;
    };

    $numeroEnTexto = function (int $numero): string {
        $numeros = [
            1 => 'UNA',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ',
        ];

        return $numeros[$numero] ?? (string) $numero;
    };

    $fraseVictimas = function (int $cantidad, string $singular, string $plural) use ($numeroEnTexto): string {
        return $numeroEnTexto($cantidad) . ' ' . ($cantidad === 1 ? $singular : $plural);
    };

    $tipoHechoParte = mb_strtoupper(trim((string) ($hechoIph['tipo_hecho'] ?? '')), 'UTF-8');
    $causasParte = mb_strtoupper(trim((string) ($hechoIph['causas'] ?? '')), 'UTF-8');
    $modalidadParte = trim(collect([
        $tipoHechoParte,
        $causasParte !== '' ? 'POR ' . $causasParte : null,
    ])->filter()->implode(' '));
    $modalidadParte = $modalidadParte !== '' ? $modalidadParte : 'HECHO DE TRÁNSITO';

    $lesionadosParte = (int) ($hechoIph['lesionados_count'] ?? 0);
    $fallecidosParte = (int) ($hechoIph['fallecidos_count'] ?? 0);
    $resultadosParte = [];

    if ($fallecidosParte > 0) {
        $resultadosParte[] = $fraseVictimas($fallecidosParte, 'PERSONA FALLECIDA', 'PERSONAS FALLECIDAS');
    }

    if ($lesionadosParte > 0) {
        $resultadosParte[] = $fraseVictimas($lesionadosParte, 'PERSONA LESIONADA', 'PERSONAS LESIONADAS');
    }

    if (!empty($resultadosParte)) {
        $ultimoResultado = array_pop($resultadosParte);
        $resultadoParte = empty($resultadosParte)
            ? $ultimoResultado
            : implode(', ', $resultadosParte) . ' Y ' . $ultimoResultado;
        $modalidadParte .= ' (CON RESULTADO DE ' . $resultadoParte . ')';
    }

    $fechaHechoParte = $valor($hechoIph['fecha'] ?? null);
    $horaHechoParte = $valor($hechoIph['hora'] ?? null);
    $calleHechoParte = $valor($ubicacion['calle'] ?? null);
    $coloniaHechoParte = $valor($ubicacion['colonia'] ?? null);
    $municipioIntervencionIph = mb_strtoupper($valor($ubicacion['municipio'] ?? $municipioEncabezado, ''), 'UTF-8');
    $entidadFederativaIph = 'MICHOACÁN';
    $unidadEconomicaIph = trim((string) ($hechoIph['unidad_numero_economico'] ?? ''));
    $unidadArriboIph = $unidadEconomicaIph !== ''
        ? 'SE ARRIBÓ EN LA UNIDAD ' . $unidadEconomicaIph
        : '';
    $referenciasIntervencionIph = trim((string) ($ubicacion['ubicacion_formateada'] ?? ''));

    $horaEnteraHecho = is_string($hechoIph['hora'] ?? null) ? (int) substr($hechoIph['hora'], 0, 2) : null;
    $momentoDia = 'Durante el día';

    if ($horaEnteraHecho !== null) {
        if ($horaEnteraHecho >= 0 && $horaEnteraHecho < 6) {
            $momentoDia = 'De madrugada';
        } elseif ($horaEnteraHecho < 12) {
            $momentoDia = 'De mañana';
        } elseif ($horaEnteraHecho < 19) {
            $momentoDia = 'De tarde';
        } else {
            $momentoDia = 'De noche';
        }
    }

    $climaHecho = mb_strtolower(trim((string) ($hechoIph['clima'] ?? '')), 'UTF-8');
    $descripcionClima = in_array($climaHecho, ['', 'bueno'], true)
        ? 'sin alteración meteorológica'
        : 'con clima ' . $climaHecho;
    $condicionesClimatologicas = $momentoDia . ', ' . $descripcionClima . '.';

    $tiempoHecho = mb_strtolower(trim((string) ($hechoIph['tiempo'] ?? '')), 'UTF-8');
    $esLuzArtificial = in_array($tiempoHecho, ['noche'], true)
        || ($horaEnteraHecho !== null && ($horaEnteraHecho < 7 || $horaEnteraHecho >= 19));
    $condicionesIluminacion = $esLuzArtificial
        ? 'Prevalecía luz artificial, emitida por las lámparas de alumbrado público que hay en el lugar.'
        : 'Prevalecía luz natural en el lugar.';
    $narrativaHechoParte = 'Por los datos e informes recabados en el lugar del hecho, mediante la inspección ocular realizada por los suscritos, se hace constar de manera preliminar la intervención correspondiente al hecho de tránsito descrito en el presente informe, quedando la narrativa pormenorizada sujeta a la complementación por el personal actuante conforme a los datos obtenidos en campo.';

    $letraIndice = function (int $index): string {
        return chr(65 + ($index % 26));
    };

    foreach ($vehiculosHecho as $index => $vehiculo) {
        if ($fotoVehiculoUrl = $imageUrl($vehiculo['foto'] ?? null)) {
            $fijacionFotos->push([
                'src' => $fotoVehiculoUrl,
                'alt' => 'Foto del vehículo ' . $letraIndice($index),
            ]);
        }
    }

    $fijacionFotos = $fijacionFotos
        ->unique('src')
        ->values();

    $textoLimpio = function ($dato): ?string {
        if (is_null($dato)) {
            return null;
        }

        $texto = trim((string) $dato);

        if ($texto === '') {
            return null;
        }

        $normalizado = mb_strtoupper($texto, 'UTF-8');

        return in_array($normalizado, ['-', 'N/A', 'NA', 'NO APLICA', 'NO ESPECIFICADO', '__________', '____'], true)
            ? null
            : $texto;
    };

    $descripcionVehiculoParte = function (array $vehiculo, int $index) use ($textoLimpio, $letraIndice): string {
        $esc = function ($dato) use ($textoLimpio): ?string {
            $texto = $textoLimpio($dato);

            return $texto !== null ? e($texto) : null;
        };

        $partes = [];

        if ($marca = $esc($vehiculo['marca'] ?? null)) {
            $partes[] = 'Marca ' . $marca;
        }

        if ($modelo = $esc($vehiculo['modelo'] ?? null)) {
            $partes[] = 'Modelo ' . $modelo;
        }

        if ($tipo = $esc($vehiculo['tipo'] ?? null)) {
            $partes[] = 'Tipo ' . $tipo;
        }

        if ($linea = $esc($vehiculo['linea'] ?? null)) {
            $partes[] = 'Línea ' . $linea;
        }

        if ($color = $esc($vehiculo['color'] ?? null)) {
            $partes[] = 'Color ' . $color;
        }

        if ($capacidad = $esc($vehiculo['capacidad_personas'] ?? null)) {
            $partes[] = 'Capacidad para ' . $capacidad . ' Personas';
        }

        if ($placas = $esc($vehiculo['placas'] ?? null)) {
            $placasTexto = 'Placas para circular <strong>' . $placas . '</strong>';

            if ($servicio = $esc($vehiculo['tipo_servicio'] ?? null)) {
                $placasTexto .= ' del servicio ' . mb_strtolower($servicio, 'UTF-8');
            }

            if ($estadoPlacas = $esc($vehiculo['estado_placas'] ?? null)) {
                $placasTexto .= ' de ' . $estadoPlacas;
            }

            $partes[] = $placasTexto;
        }

        if ($serie = $esc($vehiculo['serie'] ?? null)) {
            $partes[] = 'Serie <strong>' . $serie . '</strong>';
        }

        if ($tarjeta = $esc($vehiculo['tarjeta_circulacion_nombre'] ?? null)) {
            $partes[] = 'tarjeta de circulación a nombre de ' . $tarjeta;
        }

        $texto = '<strong>' . e('VEHICULO (' . $letraIndice($index) . ').-') . '</strong>';

        if (!empty($partes)) {
            $texto .= ' ' . implode(', ', $partes) . '.';
        }

        $conductores = $vehiculo['conductores'] ?? [];

        if (empty($conductores)) {
            return $texto;
        }

        $lineasConductores = [];

        foreach ($conductores as $conductor) {
            $nombreConductor = $textoLimpio($conductor['nombre'] ?? null);

            if (!$nombreConductor || mb_strtoupper($nombreConductor, 'UTF-8') === 'SIN CONDUCTOR') {
                continue;
            }

            $frase = 'el C. <strong>' . e(mb_strtoupper($nombreConductor, 'UTF-8')) . '</strong>';

            if ($edad = $esc($conductor['edad'] ?? null)) {
                $frase .= ' de ' . $edad . ' años de edad';
            }

            if ($domicilio = $esc($conductor['domicilio'] ?? null)) {
                $frase .= ', con domicilio en ' . $domicilio . ', en esta ciudad';
            }

            $frase .= ', me manifestó ir a bordo del vehículo';

            $licenciaPartes = array_values(array_filter([
                $textoLimpio($conductor['tipo_licencia'] ?? null),
                $textoLimpio($conductor['numero_licencia'] ?? null),
            ]));

            if (!empty($licenciaPartes)) {
                $frase .= ', presentó licencia ' . e(implode(' ', $licenciaPartes));
            }

            $lineasConductores[] = $frase . '.';
        }

        return trim($texto . ' ' . implode(' ', $lineasConductores));
    };

    $numeroALetras = function (int $numero) {
        $unidades = [
            0 => 'CERO',
            1 => 'UNO',
            2 => 'DOS',
            3 => 'TRES',
            4 => 'CUATRO',
            5 => 'CINCO',
            6 => 'SEIS',
            7 => 'SIETE',
            8 => 'OCHO',
            9 => 'NUEVE',
            10 => 'DIEZ',
            11 => 'ONCE',
            12 => 'DOCE',
            13 => 'TRECE',
            14 => 'CATORCE',
            15 => 'QUINCE',
            16 => 'DIECISÉIS',
            17 => 'DIECISIETE',
            18 => 'DIECIOCHO',
            19 => 'DIECINUEVE',
            20 => 'VEINTE',
            21 => 'VEINTIUNO',
            22 => 'VEINTIDÓS',
            23 => 'VEINTITRÉS',
            24 => 'VEINTICUATRO',
            25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS',
            27 => 'VEINTISIETE',
            28 => 'VEINTIOCHO',
            29 => 'VEINTINUEVE',
        ];
        $decenas = [30 => 'TREINTA', 40 => 'CUARENTA', 50 => 'CINCUENTA', 60 => 'SESENTA', 70 => 'SETENTA', 80 => 'OCHENTA', 90 => 'NOVENTA'];
        $centenas = [100 => 'CIEN', 200 => 'DOSCIENTOS', 300 => 'TRESCIENTOS', 400 => 'CUATROCIENTOS', 500 => 'QUINIENTOS', 600 => 'SEISCIENTOS', 700 => 'SETECIENTOS', 800 => 'OCHOCIENTOS', 900 => 'NOVECIENTOS'];

        $convertir = function (int $n) use (&$convertir, $unidades, $decenas, $centenas): string {
            if ($n < 30) {
                return $unidades[$n];
            }

            if ($n < 100) {
                $decena = (int) (floor($n / 10) * 10);
                $resto = $n % 10;

                return $resto ? $decenas[$decena] . ' Y ' . $unidades[$resto] : $decenas[$decena];
            }

            if ($n < 1000) {
                if ($n === 100) {
                    return 'CIEN';
                }

                $centena = (int) (floor($n / 100) * 100);
                $resto = $n % 100;
                $prefijo = $centena === 100 ? 'CIENTO' : $centenas[$centena];

                return $resto ? $prefijo . ' ' . $convertir($resto) : $prefijo;
            }

            if ($n < 2000) {
                $resto = $n - 1000;

                return $resto ? 'MIL ' . $convertir($resto) : 'MIL';
            }

            if ($n < 1000000) {
                $miles = (int) floor($n / 1000);
                $resto = $n % 1000;
                $texto = $convertir($miles) . ' MIL';

                return $resto ? $texto . ' ' . $convertir($resto) : $texto;
            }

            if ($n < 2000000) {
                $resto = $n - 1000000;

                return $resto ? 'UN MILLÓN ' . $convertir($resto) : 'UN MILLÓN';
            }

            $millones = (int) floor($n / 1000000);
            $resto = $n % 1000000;
            $texto = $convertir($millones) . ' MILLONES';

            return $resto ? $texto . ' ' . $convertir($resto) : $texto;
        };

        return $convertir(max(0, $numero));
    };

    $pesosEnLetra = function ($monto) use ($numeroALetras): string {
        $monto = is_numeric($monto) ? (float) $monto : 0.0;
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        if ($centavos === 100) {
            $entero++;
            $centavos = 0;
        }

        return $numeroALetras($entero) . ' PESOS ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100 M.N.';
    };

    $descripcionVictimaParte = function (array $lesionado) use ($textoLimpio): string {
        $nombre = $textoLimpio($lesionado['nombre'] ?? null);
        $edad = $textoLimpio($lesionado['edad'] ?? null);
        $tipoLesion = mb_strtoupper(trim((string) ($lesionado['tipo_lesion'] ?? '')), 'UTF-8');
        $hospital = $textoLimpio($lesionado['hospital'] ?? null);
        $ambulancia = $textoLimpio($lesionado['ambulancia'] ?? null);
        $paramedico = $textoLimpio($lesionado['paramedico'] ?? null);
        $observaciones = $textoLimpio($lesionado['observaciones'] ?? null);
        $esFallecido = $tipoLesion === 'FALLECIDO';

        if ($esFallecido) {
            $frase = $nombre
                ? 'De este hecho de tránsito resultó fallecido el C. <strong>' . e(mb_strtoupper($nombre, 'UTF-8')) . '</strong>'
                : 'De este hecho de tránsito resultó fallecida una persona';
        } else {
            $frase = $nombre
                ? 'De este hecho de tránsito resultó lesionado el C. <strong>' . e(mb_strtoupper($nombre, 'UTF-8')) . '</strong>'
                : 'De este hecho de tránsito resultó lesionada una persona';
        }

        if ($edad) {
            $frase .= ' de ' . e($edad) . ' años de edad';
        }

        if ($esFallecido) {
            $frase .= $observaciones ? ', ' . e(mb_strtolower($observaciones, 'UTF-8')) : ', quedando registrado su fallecimiento en el lugar';

            return $frase . '.';
        }

        $atenciones = [];
        $atendido = $nombre ? 'atendido' : 'atendida';
        $trasladado = $nombre ? 'trasladado' : 'trasladada';
        $valorado = $nombre ? 'valorado' : 'valorada';

        if ($paramedico) {
            $atenciones[] = $atendido . ' por el paramédico ' . e($paramedico);
        } elseif (!empty($lesionado['atencion_en_sitio'])) {
            $atenciones[] = $atendido . ' en el lugar';
        }

        if (!empty($lesionado['hospitalizado']) || $hospital) {
            $traslado = $trasladado;

            if ($hospital) {
                $traslado .= ' al nosocomio ' . e($hospital);
            } else {
                $traslado .= ' a nosocomio para su atención médica';
            }

            if ($ambulancia) {
                $traslado .= ' a bordo de la ambulancia ' . e($ambulancia);
            }

            $atenciones[] = $traslado;
        } elseif ($ambulancia) {
            $atenciones[] = $valorado . ' por la ambulancia ' . e($ambulancia);
        }

        if (!empty($atenciones)) {
            $frase .= ', quien fue ' . implode(' y ', $atenciones);
        }

        if ($observaciones) {
            $frase .= ', observándose ' . e(mb_strtolower($observaciones, 'UTF-8'));
        }

        return $frase . '.';
    };

    $descripcionDaniosVehiculo = function (array $vehiculo, int $index) use ($textoLimpio, $letraIndice, $pesosEnLetra): string {
        $partes = $textoLimpio($vehiculo['partes_danadas'] ?? null);
        $monto = $vehiculo['monto_danos'] ?? null;
        $montoValido = is_numeric($monto) && (float) $monto > 0;
        $frase = '<strong>' . e('VEHÍCULO (' . $letraIndice($index) . ').-') . '</strong> ';

        if ($partes) {
            $frase .= 'Presenta daños en su ' . e(mb_strtolower($partes, 'UTF-8'));
        } else {
            $frase .= 'No se cuenta con partes dañadas registradas en el sistema';
        }

        if ($montoValido) {
            $montoNumero = (float) $monto;
            $frase .= ', se estiman en la cantidad aproximada para su reparación de $ '
                . number_format($montoNumero, 2)
                . ' (' . e($pesosEnLetra($montoNumero)) . ')';
        }

        return $frase . '.';
    };

    $valorGruaValido = function ($valor) use ($textoLimpio): ?string {
        $texto = $textoLimpio($valor);

        if (!$texto) {
            return null;
        }

        $normalizado = mb_strtoupper($texto, 'UTF-8');

        return in_array($normalizado, ['0', 'NO', 'SIN GRUA', 'SIN GRÚA', 'SIN CORRALON', 'SIN CORRALÓN'], true)
            ? null
            : $texto;
    };

    $textoVehiculosParte = function (int $total): string {
        if ($total === 1) {
            return 'El vehículo';
        }

        if ($total === 2) {
            return 'Ambos vehículos';
        }

        return 'Los vehículos';
    };

    $gruasParte = collect($vehiculosHecho)
        ->map(function (array $vehiculo) use ($valorGruaValido) {
            $nombre = $valorGruaValido($vehiculo['grua_nombre'] ?? null)
                ?: $valorGruaValido($vehiculo['grua'] ?? null);
            $direccion = $valorGruaValido($vehiculo['grua_direccion'] ?? null)
                ?: $valorGruaValido($vehiculo['grua_ubicacion_corralon'] ?? null)
                ?: $valorGruaValido($vehiculo['corralon'] ?? null);

            if (!$nombre && !$direccion) {
                return null;
            }

            return [
                'nombre' => $nombre,
                'direccion' => $direccion,
            ];
        })
        ->filter()
        ->unique(function (array $grua) {
            return mb_strtoupper(($grua['nombre'] ?? '') . '|' . ($grua['direccion'] ?? ''), 'UTF-8');
        })
        ->values();

    $observacionesGruasParte = function () use ($gruasParte, $textoVehiculosParte, $vehiculosHecho): string {
        $totalVehiculos = count($vehiculosHecho);
        $sujeto = $textoVehiculosParte($totalVehiculos);
        $verbo = $totalVehiculos === 1 ? 'fue' : 'fueron';
        $resguardado = $totalVehiculos === 1 ? 'resguardado' : 'resguardados';

        if ($gruasParte->isEmpty()) {
            return $sujeto . ($totalVehiculos === 1 ? ' no cuenta' : ' no cuentan') . ' con registro de traslado o resguardo por grúa en el sistema.';
        }

        $nombres = $gruasParte
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->map(fn ($nombre) => '<strong>' . e($nombre) . '</strong>')
            ->implode(' y ');
        $direcciones = $gruasParte
            ->pluck('direccion')
            ->filter()
            ->unique()
            ->map(fn ($direccion) => '<strong>' . e($direccion) . '</strong>')
            ->implode(' y ');

        $frase = $sujeto . ' ' . $verbo . ' ' . $resguardado;

        if ($nombres !== '') {
            $frase .= ' con apoyo de ' . $nombres;
        }

        if ($direcciones !== '' && $nombres !== '') {
            $frase .= ', garaje de apoyo a esta dependencia, ubicado en ' . $direcciones;
        } elseif ($direcciones !== '') {
            $frase .= ' en ' . $direcciones;
        }

        return $frase . '.';
    };

    $resultadoLegalParte = function () use ($lesionadosParte, $fallecidosParte): string {
        $resultados = [];

        if ($lesionadosParte > 0) {
            $resultados[] = 'lesiones';
        }

        if ($fallecidosParte > 0) {
            $resultados[] = 'fallecimiento';
        }

        $resultados[] = 'daños materiales';

        if (count($resultados) === 1) {
            return $resultados[0];
        }

        $ultimo = array_pop($resultados);

        return implode(', ', $resultados) . ' y ' . $ultimo;
    };

    $conclusionCausaParte = function () use ($causasParte, $resultadoLegalParte): string {
        $causa = trim((string) $causasParte);
        $causaTexto = $causa !== ''
            ? mb_strtolower($causa, 'UTF-8')
            : 'la falta de precaución y cuidado';

        return '<strong>ÚNICA.-</strong> La causa que da origen al hecho de tránsito que nos ocupa se refiere a '
            . e($causaTexto)
            . ' por parte del conductor del vehículo <strong>(A)</strong>, en consecuencia ocasionar '
            . e($resultadoLegalParte())
            . ', violando por tal motivo el <u>artículo</u> 432 Fracción V, del Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.';
    };

    $conclusionDisposicionParte = function () use ($gruasParte, $textoVehiculosParte, $vehiculosHecho): string {
        $vehiculosTexto = mb_strtolower($textoVehiculosParte(count($vehiculosHecho)), 'UTF-8');
        $frase = 'Con base en lo dispuesto en el artículo 59 de la Ley de Tránsito y Vialidad vigente en el Estado, pongo a su disposición ' . e($vehiculosTexto);
        $nombres = $gruasParte
            ->pluck('nombre')
            ->filter()
            ->unique()
            ->map(fn ($nombre) => '<strong>' . e($nombre) . '</strong>')
            ->implode(' y ');

        if ($nombres !== '') {
            $frase .= ', en las instalaciones de ' . $nombres . ', garaje de apoyo a esta dependencia';
        }

        return $frase . ', lo anterior para los fines legales a los que haya lugar.';
    };
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPH Puesta a Disposición - Hecho {{ $hecho->id }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #4b5563;
            --line: #cbd5e1;
            --soft: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e7eb;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.35;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: #111827;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .18);
        }

        .toolbar button,
        .toolbar a {
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 4px;
            padding: 7px 12px;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .sheet {
            width: 216mm;
            min-height: 356mm;
            margin: 14px auto;
            padding: 13mm;
            background: #fff;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .18);
        }

        .letterhead {
            display: grid;
            grid-template-columns: 78mm minmax(0, 1fr);
            column-gap: 12mm;
            align-items: start;
            margin-bottom: 14px;
        }

        .letterhead-logo {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            padding-top: 7mm;
        }

        .letterhead-logo img {
            width: 78mm;
            max-width: 100%;
            height: auto;
            display: block;
        }

        .letterhead-office {
            padding-top: 0;
        }

        .office-row {
            display: grid;
            grid-template-columns: 42% 58%;
            align-items: center;
            min-height: 24px;
            margin-bottom: 2px;
            overflow: hidden;
            border-radius: 6px;
            background: #e9e9e9;
        }

        .office-label {
            padding: 4px 10px;
            color: #333;
            font-size: 13px;
            font-weight: 400;
        }

        .office-label.is-empty {
            color: transparent;
        }

        .office-value {
            padding: 4px 10px;
            color: #000;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            word-break: break-word;
        }

        .office-asunto {
            margin-top: 6px;
            padding-left: 10px;
            color: #333;
            font-size: 13px;
            font-weight: 700;
        }

        .letterhead-place-date {
            margin-top: 25px;
            color: #000;
            font-size: 16px;
            line-height: 1.2;
            text-align: right;
        }

        .letterhead-place-date strong {
            font-weight: 700;
        }

        .document-title {
            margin: 20px 0 12px;
            text-align: center;
            text-transform: uppercase;
        }

        .document-title h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 0;
        }

        .document-title p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 11px;
        }

        .report-section {
            margin-top: 28px;
            color: #000;
            font-size: 16px;
            line-height: 1.16;
        }

        .report-heading {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .report-heading .roman {
            display: inline-block;
            width: 40mm;
            text-align: right;
            padding-right: 8mm;
        }

        .report-paragraph {
            margin: 42px 0 0;
            text-align: justify;
            text-indent: 28mm;
        }

        .report-paragraph strong {
            font-weight: 700;
        }

        .method-list {
            margin: 34px 0 0;
            padding: 0;
            list-style-position: inside;
        }

        .method-list li {
            margin-top: 26px;
            text-align: justify;
            text-indent: 28mm;
        }

        .material-list {
            margin: 36px 0 0 27mm;
            padding: 0;
            font-size: 14px;
            line-height: 1.25;
        }

        .material-list li {
            padding-left: 9mm;
        }

        .informative-opening {
            margin-top: 18px;
            color: #000;
            font-size: 16px;
            line-height: 1.12;
        }

        .informative-addressee {
            width: 62%;
            font-weight: 700;
            text-transform: uppercase;
        }

        .informative-present {
            display: block;
            margin-top: 0;
            letter-spacing: 6px;
        }

        .informative-paragraph {
            margin: 34px 0 0;
            text-align: justify;
            text-indent: 28mm;
        }

        .informative-paragraph strong {
            font-weight: 700;
        }

        .compact-list {
            margin: 24px 0 0 27mm;
            padding: 0;
            font-size: 14px;
            line-height: 1.35;
        }

        .compact-list li {
            padding-left: 9mm;
        }

        .vehicle-description {
            margin-top: 34px;
        }

        .vehicle-description p {
            margin: 0 0 30px;
            text-align: justify;
            text-indent: 28mm;
            line-height: 1.12;
        }

        .dynamics-section {
            margin-top: 0;
        }

        .page-logo {
            margin: 0 0 24px;
        }

        .page-logo img {
            width: 78mm;
            max-width: 100%;
            height: auto;
            display: block;
        }

        .diagram-heading {
            margin-top: 58px;
        }

        .croquis-preview-wrap {
            margin-top: 28px;
            text-align: center;
        }

        .croquis-preview-wrap img {
            display: block;
            width: 100%;
            max-width: 176mm;
            max-height: 168mm;
            margin: 0 auto;
            object-fit: contain;
        }

        .croquis-empty {
            height: 118mm;
        }

        .photo-fixation-section {
            margin-top: 0;
        }

        .photo-list {
            margin-top: 32px;
        }

        .photo-item {
            margin: 0 0 22mm;
            text-align: center;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .photo-item img {
            display: block;
            width: 158mm;
            max-width: 100%;
            max-height: 118mm;
            margin: 0 auto;
            object-fit: contain;
        }

        .photo-empty {
            height: 118mm;
        }

        .summary-section {
            margin-top: 0;
        }

        .summary-block {
            margin-top: 28px;
        }

        .summary-paragraph {
            margin: 24px 0 0;
            text-align: justify;
            text-indent: 28mm;
            line-height: 1.14;
        }

        .summary-paragraph strong {
            font-weight: 700;
        }

        .damage-description {
            margin-top: 22px;
        }

        .damage-description p {
            margin: 0 0 22px;
            text-align: justify;
            text-indent: 28mm;
            line-height: 1.14;
        }

        .conclusions-title {
            margin: 42px 0 0;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .signature-block {
            margin-top: 42px;
            text-align: center;
            text-transform: uppercase;
        }

        .signature-role {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.12;
        }

        .signature-name {
            margin: 72px 0 0;
            font-size: 13px;
            font-weight: 700;
        }

        .iph-front {
            margin-top: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.12;
        }

        .iph-top {
            display: grid;
            grid-template-columns: minmax(0, 74mm) minmax(0, 1fr);
            column-gap: 5mm;
            align-items: start;
            max-width: 100%;
        }

        .iph-national {
            padding-top: 10px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-national img {
            display: block;
            width: 28mm;
            height: auto;
            margin: 12px auto 4px;
        }

        .iph-reference {
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            border: 1px solid #1f2933;
            padding: 4px 5px 5px;
            text-align: center;
        }

        .iph-reference-title,
        .iph-system-folio-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-reference-row {
            display: flex;
            justify-content: center;
            gap: 1px;
            margin-top: 4px;
        }

        .iph-reference-group {
            display: grid;
            justify-items: center;
            gap: 2px;
        }

        .iph-boxes {
            display: flex;
            gap: 1px;
        }

        .iph-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4.25mm;
            height: 5mm;
            border: 1px solid #1f2933;
            background: #fff;
            font-size: 10.5px;
            font-weight: 700;
            line-height: 1;
        }

        .iph-box.is-cream,
        .iph-label.is-cream,
        .iph-bar.is-cream {
            background: #eee2c8;
        }

        .iph-label {
            min-width: 100%;
            padding: 1px 2px;
            font-size: 7px;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
        }

        .iph-system-folio {
            margin-top: 5px;
        }

        .iph-system-boxes {
            display: flex;
            justify-content: center;
            gap: 1px;
            margin-top: 3px;
        }

        .iph-main-title {
            margin-top: 16px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-bar {
            border: 1px solid #1f2933;
            padding: 2px 6px;
            background: #6f7f7e;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .iph-bar.is-cream {
            color: #000;
        }

        .iph-section-title {
            margin-top: 13px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-subbar {
            border: 1px solid #1f2933;
            padding: 3px 7px;
            background: #eee2c8;
            color: #000;
            font-size: 11px;
            font-weight: 700;
        }

        .iph-panel {
            border: 1px solid #1f2933;
            border-top: 0;
        }

        .iph-date-row {
            display: grid;
            grid-template-columns: 30% 25% 45%;
            min-height: 24mm;
            border-bottom: 1px solid #1f2933;
        }

        .iph-date-field {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 8px;
            border-right: 1px solid #1f2933;
        }

        .iph-date-field:last-child {
            border-right: 0;
        }

        .iph-date-label {
            font-size: 10px;
        }

        .iph-date-boxes {
            display: flex;
            align-items: center;
            gap: 1px;
        }

        .iph-date-caption {
            margin-top: 1px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 4px;
            text-align: center;
        }

        .iph-colon {
            padding: 0 2px;
            font-size: 14px;
            font-weight: 700;
        }

        .iph-help {
            padding: 4px 7px;
            border-bottom: 1px solid #1f2933;
            font-size: 9px;
            font-style: italic;
        }

        .iph-annex-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid #1f2933;
        }

        .iph-annex-column {
            padding: 4px 7px;
            border-right: 1px solid #1f2933;
        }

        .iph-annex-column:last-child {
            border-right: 0;
        }

        .iph-annex-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 8px;
            align-items: center;
            min-height: 9mm;
            font-size: 10px;
        }

        .iph-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4mm;
            height: 4mm;
            border: 1px solid #1f2933;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
        }

        .iph-small-boxes .iph-box {
            width: 5mm;
            height: 5mm;
            font-size: 10px;
        }

        .iph-doc-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .iph-doc-left,
        .iph-doc-right {
            min-height: 26mm;
            padding: 5px 8px;
        }

        .iph-doc-left {
            display: grid;
            grid-template-columns: 35% 65%;
            align-items: center;
            border-right: 1px solid #1f2933;
        }

        .iph-doc-options {
            display: grid;
            grid-template-columns: auto auto 1fr;
            row-gap: 5px;
            column-gap: 5px;
            align-items: center;
        }

        .iph-doc-right {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 14px;
            align-content: start;
        }

        .iph-doc-option {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 8px;
        }

        .iph-inventory-heading {
            margin-top: 4px;
            font-size: 16px;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
        }

        .iph-person-section {
            border-top: 1px solid #1f2933;
        }

        .iph-person-title {
            border-bottom: 1px solid #1f2933;
            padding: 3px 7px;
            background: #eee2c8;
            font-size: 11px;
            font-weight: 700;
        }

        .iph-line-row {
            display: grid;
            grid-template-columns: 32mm minmax(0, 1fr);
            align-items: end;
            min-height: 7.6mm;
            border-bottom: 1px solid #1f2933;
        }

        .iph-line-row:last-child {
            border-bottom: 0;
        }

        .iph-line-label {
            padding-left: 7px;
            font-size: 10px;
            font-weight: 700;
        }

        .iph-line-value {
            min-height: 6mm;
            padding: 2px 8px 1px;
            border-left: 1px solid #1f2933;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-seal-box {
            min-height: 23mm;
            display: flex;
            align-items: end;
            justify-content: center;
            padding-bottom: 4px;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
        }

        .iph-continuation {
            margin-top: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.1;
        }

        .iph-form-section {
            margin-top: 8px;
            border: 1px solid #1f2933;
        }

        .iph-form-section:first-child {
            margin-top: 0;
        }

        .iph-section-caption {
            padding: 3px 7px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .iph-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border-bottom: 1px solid #1f2933;
        }

        .iph-grid-cell {
            min-height: 12mm;
            padding: 4px 8px 2px;
            border-right: 1px solid #1f2933;
            text-align: center;
        }

        .iph-grid-cell:last-child {
            border-right: 0;
        }

        .iph-big-value {
            min-height: 6mm;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-small-label {
            border-top: 1px solid #1f2933;
            padding-top: 2px;
            font-size: 9px;
        }

        .iph-authority-grid {
            display: grid;
            grid-template-columns: 30% 30% 40%;
            gap: 0;
            padding: 5px 8px;
            border-bottom: 1px solid #1f2933;
        }

        .iph-authority-row {
            display: flex;
            align-items: center;
            gap: 5px;
            min-height: 7mm;
        }

        .iph-free-line {
            min-width: 0;
            border-bottom: 1px solid #1f2933;
            height: 5mm;
            flex: 1;
        }

        .iph-responder-lines {
            padding: 4px 8px;
            border-bottom: 1px solid #1f2933;
        }

        .iph-responder-line {
            display: grid;
            grid-template-columns: 52mm minmax(0, 1fr);
            align-items: end;
            min-height: 7mm;
        }

        .iph-responder-value {
            border-bottom: 1px solid #1f2933;
            min-height: 5mm;
            padding: 0 6px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .iph-count-row {
            display: grid;
            grid-template-columns: 1fr auto 6mm 22mm auto auto 6mm;
            gap: 5px;
            align-items: center;
            padding: 4px 8px;
            min-height: 9mm;
        }

        .iph-knowledge-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 2mm 5mm;
            padding: 6px 8px;
            border-bottom: 1px solid #1f2933;
        }

        .iph-knowledge-option {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 6px;
            min-height: 6mm;
        }

        .iph-911-row {
            display: grid;
            grid-template-columns: 24mm minmax(0, auto) 1fr;
            gap: 6px;
            align-items: center;
            padding: 5px 8px;
            border-bottom: 1px solid #1f2933;
        }

        .iph-time-boxes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26mm;
            padding: 10px 25mm 12px;
            border-bottom: 1px solid #1f2933;
        }

        .iph-time-card {
            border: 1px solid #1f2933;
            padding: 6px;
            text-align: center;
        }

        .iph-time-title {
            margin-bottom: 5px;
            font-size: 9px;
            font-weight: 700;
            font-style: italic;
        }

        .iph-date-line,
        .iph-time-line {
            display: grid;
            grid-template-columns: 15mm minmax(0, 1fr);
            align-items: center;
            gap: 5px;
            margin-top: 4px;
            text-align: left;
        }

        .iph-geo-lines {
            padding: 3px 8px 8px;
        }

        .iph-geo-row {
            display: grid;
            grid-template-columns: 40mm minmax(0, 1fr);
            align-items: end;
            min-height: 7.2mm;
        }

        .iph-geo-row.two-col {
            grid-template-columns: 25mm 45mm 28mm 45mm;
            gap: 6px;
        }

        .iph-geo-row.three-col {
            grid-template-columns: 22mm 27mm 23mm 27mm 30mm minmax(0, 1fr);
            gap: 6px;
        }

        .iph-geo-value {
            min-height: 5mm;
            border-bottom: 1px solid #1f2933;
            padding: 1px 6px 0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .iph-coordinate-row {
            display: grid;
            grid-template-columns: 22mm minmax(0, auto) 24mm minmax(0, auto);
            align-items: center;
            gap: 6px;
            padding-top: 3px;
        }

        .iph-coordinate-row .iph-box {
            width: 4mm;
        }

        .force-next-page {
            break-after: page;
            page-break-after: always;
        }

        .section {
            margin-top: 12px;
            border: 1px solid var(--line);
        }

        .section-title {
            padding: 6px 8px;
            background: var(--ink);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--line);
        }

        .field {
            min-height: 44px;
            padding: 6px 7px;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .field:nth-child(4n) {
            border-right: 0;
        }

        .field.wide {
            grid-column: span 2;
        }

        .field.full {
            grid-column: 1 / -1;
            border-right: 0;
        }

        .label {
            display: block;
            margin-bottom: 3px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .value {
            white-space: pre-wrap;
            word-break: break-word;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid var(--line);
            vertical-align: top;
            text-align: left;
        }

        th {
            background: var(--soft);
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 28px;
        }

        .signature {
            padding-top: 34px;
            border-top: 1px solid var(--ink);
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .hint {
            margin-top: 8px;
            color: var(--muted);
            font-size: 10px;
        }

        @page {
            size: legal;
            margin: 10mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .section {
                break-inside: avoid;
            }

            .letterhead {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir</button>
        <a href="{{ route('hechos.show', $hecho->id) }}">Volver al hecho</a>
    </div>

    <main class="sheet">
        <header class="letterhead" aria-label="Membrete institucional">
            <div class="letterhead-logo">
                <img src="{{ $sspHorizontal }}" alt="Secretaría de Seguridad Pública Gobierno de Michoacán">
            </div>
            <div class="letterhead-office">
                <div class="office-row">
                    <div class="office-label">Dependencia</div>
                    <div class="office-value">Secretaría de Seguridad Pública</div>
                </div>
                <div class="office-row">
                    <div class="office-label is-empty">Dependencia</div>
                    <div class="office-value">Del Estado de Michoacán de Ocampo</div>
                </div>
                <div class="office-row">
                    <div class="office-label">Sub-dependencia</div>
                    <div class="office-value">&nbsp;</div>
                </div>
                <div class="office-row">
                    <div class="office-label">Oficina</div>
                    <div class="office-value">{{ $oficinaEncabezado }}</div>
                </div>
                <div class="office-row">
                    <div class="office-label">No. de oficio</div>
                    <div class="office-value">{{ $valor($oficioEncabezado, '') }}</div>
                </div>
                <div class="office-row">
                    <div class="office-label">Expediente</div>
                    <div class="office-value">{{ $valor($expedienteEncabezado, '') }}</div>
                </div>
                <div class="office-asunto">Asunto:</div>
                <div class="letterhead-place-date">
                    {{ $municipioEncabezado }} Michoacán a <strong>{{ $fechaEncabezado }}.</strong>
                </div>
            </div>
        </header>

        <section class="informative-opening">
            <div class="informative-addressee">
                DIRECCION DE CARPETAS DE INVESTIGACION DE LA FISCALIA GENERAL DE JUSTICIA EN EL ESTADO.
                <span class="informative-present">PRESENTE.</span>
            </div>
            <p class="informative-paragraph">
                El suscrito Perito en Hechos de Tránsito <strong>{{ $nombreCreadorHecho }}</strong>,
                adscrito a la Coordinación del Agrupamiento de Seguridad Vial, de la Secretaría de Seguridad Pública del Estado,
                tengo a bien emitir el siguiente:
            </p>
        </section>

        <header class="document-title">
            <h1>Parte Informativo</h1>
        </header>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">I.</span> PLANTEAMIENTO DEL PROBLEMA:</h2>
            <p class="report-paragraph">
                Establecer las causas que originaron el hecho de tránsito terrestre en su modalidad de
                <strong>({{ $modalidadParte }})</strong>, ocurrido el día {{ $fechaHechoParte }},
                a las {{ $horaHechoParte }} horas en {{ $calleHechoParte }},
                de la colonia <strong>{{ $coloniaHechoParte }}</strong>, en esta ciudad.
            </p>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">II.</span> METODOLOGÍA APLICADA AL PRESENTE INFORME PERICIAL:</h2>
            <p class="report-paragraph">
                La metodología propuesta por el método científico, en cuanto al planteamiento del problema,
                la recopilación de datos por medio de la observación metódica y directa.
            </p>
            <p class="report-paragraph">
                Para realizar el presente Parte Informativo aplicaremos:
            </p>
            <ul class="method-list">
                <li>Método inductivo es un método del que se obtienen conclusiones generales a partir de las premisas particulares.</li>
                <li>Método deductivo un método el cual se utiliza para interpretar hechos particulares a través de una ley general establecida y se deriva de hechos similares, al del objeto de estudio.</li>
            </ul>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">III.</span> MATERIAL UTILIZADO:</h2>
            <ul class="material-list">
                <li>Libreta de anotaciones, lapicero de punto medio.</li>
                <li>Cámara fotográfica digital.</li>
                <li>Cinta métrica.</li>
                <li>Brújula Digital para señalar la orientación.</li>
            </ul>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">IV.</span> OBJETIVOS:</h2>
            <p class="report-paragraph">
                Contribuir con información sobre los datos e indicios recabados en el lugar.
            </p>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">V.</span> FIJACIÓN DEL LUGAR DE LA INTERVENCIÓN:</h2>
            <ul class="compact-list">
                <li>Fotográfica</li>
                <li>Escrita</li>
                <li>Planimetría</li>
            </ul>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">VI.</span> CONDICIONES CLIMATOLÓGICAS:</h2>
            <p class="report-paragraph">
                {{ $condicionesClimatologicas }}
            </p>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">VII.</span> CONDICIONES DE ILUMINACIÓN:</h2>
            <p class="report-paragraph">
                {{ $condicionesIluminacion }}
            </p>
        </section>

        <section class="report-section">
            <h2 class="report-heading"><span class="roman">VIII.</span> DESCRIPCIÓN DEL LUGAR DE LOS HECHOS:</h2>
            <p class="report-paragraph">
                Corresponde a {{ $calleHechoParte }}, la cual se encuentra construida por una superficie de concreto,
                en buen estado de conservación, tramo a nivel, cuenta con paramentos a sus costados, tiene capacidad
                para dos carriles de circulación, uno para cada sentido, orientados de oriente a poniente y viceversa,
                a la hora de la intervención la superficie de rodamiento se encontraba limpia y seca.
            </p>
        </section>

        <section class="report-section force-next-page">
            <h2 class="report-heading"><span class="roman">IX.</span> DESCRIPCIÓN DE VEHÍCULOS:</h2>
            <div class="vehicle-description">
                @forelse($vehiculosHecho as $vehiculo)
                    <p>{!! $descripcionVehiculoParte($vehiculo, $loop->index) !!}</p>
                @empty
                    <p>No se cuenta con vehículos registrados en el hecho.</p>
                @endforelse
            </div>
        </section>

        <section class="report-section dynamics-section force-next-page">
            <div class="page-logo">
                <img src="{{ $sspHorizontal }}" alt="Secretaría de Seguridad Pública Gobierno de Michoacán">
            </div>

            <h2 class="report-heading"><span class="roman">X.</span> DINÁMICA DEL HECHO DE TRÁNSITO:</h2>
            <p class="report-paragraph">
                {{ $narrativaHechoParte }}
            </p>

            <h2 class="report-heading diagram-heading"><span class="roman">XI.</span> DIAGRAMA ILUSTRATIVO NO HECHO A ESCALA.</h2>
            <div class="croquis-preview-wrap">
                @if($croquisPreviewUrl)
                    <img src="{{ $croquisPreviewUrl }}" alt="Croquis del lugar del hecho">
                @else
                    <div class="croquis-empty" aria-hidden="true"></div>
                @endif
            </div>
        </section>

        <section class="report-section photo-fixation-section force-next-page">
            <div class="page-logo">
                <img src="{{ $sspHorizontal }}" alt="Secretaría de Seguridad Pública Gobierno de Michoacán">
            </div>

            <h2 class="report-heading"><span class="roman">XII.</span> FIJACIÓN FOTOGRÁFICA.</h2>
            <div class="photo-list">
                @forelse($fijacionFotos as $foto)
                    <div class="photo-item">
                        <img src="{{ $foto['src'] }}" alt="{{ $foto['alt'] }}">
                    </div>
                @empty
                    <div class="photo-empty" aria-hidden="true"></div>
                @endforelse
            </div>
        </section>

        <section class="report-section summary-section force-next-page">
            <div class="page-logo">
                <img src="{{ $sspHorizontal }}" alt="Secretaría de Seguridad Pública Gobierno de Michoacán">
            </div>

            <div class="summary-block">
                <h2 class="report-heading"><span class="roman">XIII.-</span> VÍCTIMAS:</h2>
                @forelse($lesionadosHecho as $lesionado)
                    <p class="summary-paragraph">{!! $descripcionVictimaParte($lesionado) !!}</p>
                @empty
                    <p class="summary-paragraph">De este hecho de tránsito no se manifestaron víctimas ante el suscrito.</p>
                @endforelse
            </div>

            <div class="summary-block">
                <h2 class="report-heading"><span class="roman">XIV.-</span> DAÑOS:</h2>
                <div class="damage-description">
                    @forelse($vehiculosHecho as $vehiculo)
                        <p>{!! $descripcionDaniosVehiculo($vehiculo, $loop->index) !!}</p>
                    @empty
                        <p>No se cuenta con vehículos registrados para estimación de daños.</p>
                    @endforelse
                </div>
                @if(count($vehiculosHecho) > 0)
                    <p class="summary-paragraph">
                        Estos daños fueron estimados y calculados a simple vista y será salvo el presupuesto real que le sea
                        presentado ante usted por las partes involucradas una vez que hayan sido desarmadas todas y cada una
                        de las piezas dañadas.
                    </p>
                @endif
            </div>

            <div class="summary-block">
                <h2 class="report-heading"><span class="roman">XV.-</span> OBSERVACIONES:</h2>
                <p class="summary-paragraph">{!! $observacionesGruasParte() !!}</p>
                <p class="summary-paragraph">De lo anteriormente expuesto y formulado se llega a las siguientes:</p>
            </div>

            <div class="summary-block">
                <h2 class="conclusions-title">CONCLUSIONES:</h2>
                <p class="summary-paragraph">{!! $conclusionCausaParte() !!}</p>
                <p class="summary-paragraph">{!! $conclusionDisposicionParte() !!}</p>
            </div>

            <div class="signature-block">
                <p class="signature-role">ATENTAMENTE.</p>
                <p class="signature-role">PERITO DE TRÁNSITO.</p>
                <p class="signature-name">{{ $nombreCreadorHecho }}</p>
            </div>
        </section>

        <section class="iph-front force-next-page">
            <div class="iph-top">
                <div class="iph-national">
                    <div>Sistema Nacional de Seguridad Pública</div>
                    <img src="{{ $mexicoLogo }}" alt="Escudo nacional CNSP">
                    <div>CNSP</div>
                </div>

                <div class="iph-reference">
                    <div class="iph-reference-title">No. de referencia</div>
                    <div class="iph-reference-row">
                        @foreach($referenciaIph as $grupo)
                            <div class="iph-reference-group">
                                <div class="iph-boxes">
                                    {!! $renderIphBoxes($grupo['value'], $grupo['length'] ?? max(1, mb_strlen((string) $grupo['value'], 'UTF-8')), $grupo['cream'] ? 'is-cream' : '') !!}
                                </div>
                                <div class="iph-label {{ $grupo['cream'] ? 'is-cream' : '' }}">{{ $grupo['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="iph-system-folio">
                        <div class="iph-system-folio-title">No. de folio asignado por el sistema</div>
                        <div class="iph-system-boxes">
                            {!! $renderIphBoxes('', 20) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="iph-main-title">Informe Policial Homologado (IPH<sub>2019</sub>)</div>
            <div class="iph-bar is-cream">Hecho probablemente delictivo</div>

            <div class="iph-section-title">Sección 1. Puesta a disposición</div>
            <div class="iph-subbar">Apartado 1.1. Fecha y hora de la puesta a disposición.</div>
            <div class="iph-panel">
                <div class="iph-date-row">
                    <div class="iph-date-field">
                        <span class="iph-date-label">Fecha:</span>
                        <div>
                            <div class="iph-date-boxes">
                                {!! $renderIphBoxes($fechaPuestaPartes['dia'] . $fechaPuestaPartes['mes'] . $fechaPuestaPartes['anio'], 8) !!}
                            </div>
                            <div class="iph-date-caption">DDMMAAAA</div>
                        </div>
                    </div>
                    <div class="iph-date-field">
                        <span class="iph-date-label">Hora</span>
                        <div>
                            <div class="iph-date-boxes">
                                {!! $renderIphBoxes('', 2) !!}
                                <span class="iph-colon">:</span>
                                {!! $renderIphBoxes('', 2) !!}
                            </div>
                            <div class="iph-date-caption">hh&nbsp;&nbsp;&nbsp;mm (24 Horas)</div>
                        </div>
                    </div>
                    <div class="iph-date-field">
                        <span class="iph-date-label">No. de expediente:</span>
                        <div class="iph-date-boxes">
                            {!! $renderIphBoxes($expedienteIph, 18) !!}
                        </div>
                    </div>
                </div>

                <div class="iph-help">
                    Señale con una "X" el o los Anexos entregados e indique la cantidad de cada uno de ellos (sólo entregue los Anexos utilizados)
                </div>

                <div class="iph-annex-grid">
                    <div class="iph-annex-column">
                        <div class="iph-annex-row">
                            <span><strong>Anexo A.</strong> Detención(es)</span>
                            <span class="iph-check">{{ count($personasPuesta) > 0 ? 'X' : '' }}</span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes(count($personasPuesta) > 0 ? str_pad((string) count($personasPuesta), 3, '0', STR_PAD_LEFT) : '', 3) !!}</span>
                        </div>
                        <div class="iph-annex-row">
                            <span><strong>Anexo B.</strong> Informe de uso de la fuerza</span>
                            <span class="iph-check"></span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes('', 3) !!}</span>
                        </div>
                        <div class="iph-annex-row">
                            <span><strong>Anexo C.</strong> Inspección de vehículo</span>
                            <span class="iph-check">{{ count($vehiculosHecho) > 0 ? 'X' : '' }}</span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes(count($vehiculosHecho) > 0 ? $vehiculosAnexoCantidad : '', 3) !!}</span>
                        </div>
                        <div class="iph-annex-row">
                            <span><strong>Anexo D.</strong> Inventario de armas y objetos</span>
                            <span class="iph-check">{{ count($objetosPuesta) > 0 ? 'X' : '' }}</span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes(count($objetosPuesta) > 0 ? str_pad((string) count($objetosPuesta), 3, '0', STR_PAD_LEFT) : '', 3) !!}</span>
                        </div>
                    </div>

                    <div class="iph-annex-column">
                        <div class="iph-annex-row">
                            <span><strong>Anexo E.</strong> Entrevistas</span>
                            <span class="iph-check"></span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes('', 3) !!}</span>
                        </div>
                        <div class="iph-annex-row">
                            <span><strong>Anexo F.</strong> Entrega - recepción del lugar de la intervención</span>
                            <span class="iph-check"></span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes('', 3) !!}</span>
                        </div>
                        <div class="iph-annex-row">
                            <span><strong>Anexo G.</strong> Continuación de la narrativa de los hechos y/o entrevista</span>
                            <span class="iph-check"></span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes('', 3) !!}</span>
                        </div>
                        <div class="iph-annex-row">
                            <span>No se entregan anexos</span>
                            <span class="iph-check"></span>
                            <span class="iph-small-boxes">{!! $renderIphBoxes('', 3) !!}</span>
                        </div>
                    </div>
                </div>

                <div class="iph-doc-row">
                    <div class="iph-doc-left">
                        <div>¿Anexa documentación complementaria?</div>
                        <div class="iph-doc-options">
                            <span>Sí</span>
                            <span class="iph-check">{{ ($fijacionFotos->isNotEmpty() || count($vehiculosHecho) > 0 || !empty($lesionadosHecho)) ? 'X' : '' }}</span>
                            <span><em>(Señale con una "X" el tipo de documentación)</em></span>
                            <span>No</span>
                            <span class="iph-check">{{ ($fijacionFotos->isEmpty() && count($vehiculosHecho) === 0 && empty($lesionadosHecho)) ? 'X' : '' }}</span>
                            <span></span>
                        </div>
                    </div>
                    <div class="iph-doc-right">
                        <div class="iph-doc-option"><span>Fotografías</span><span class="iph-check">{{ $fijacionFotos->isNotEmpty() ? 'X' : '' }}</span></div>
                        <div class="iph-doc-option"><span>Audio</span><span class="iph-check"></span></div>
                        <div class="iph-doc-option"><span>Videos</span><span class="iph-check"></span></div>
                        <div class="iph-doc-option"><span>Certificados médicos</span><span class="iph-check">{{ !empty($lesionadosHecho) ? 'X' : '' }}</span></div>
                        <div class="iph-doc-option"><span>Otra</span><span class="iph-check"></span></div>
                        <div class="iph-doc-option"><span>¿Cuál?</span><span></span></div>
                        <div class="iph-inventory-heading">Inventario de los vehículos</div>
                    </div>
                </div>

                <div class="iph-person-section">
                    <div class="iph-person-title">Datos de quien realiza la puesta a disposición</div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Primer apellido:</div>
                        <div class="iph-line-value">{{ $nombreQuienPonePartes['primer_apellido'] }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Segundo apellido:</div>
                        <div class="iph-line-value">{{ $nombreQuienPonePartes['segundo_apellido'] }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Nombre (s):</div>
                        <div class="iph-line-value">{{ $nombreQuienPonePartes['nombres'] }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Adscripción:</div>
                        <div class="iph-line-value">{{ $adscripcionQuienPoneIph }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Cargo/grado:</div>
                        <div class="iph-line-value">{{ $cargoQuienPoneIph }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Firma:</div>
                        <div class="iph-line-value">&nbsp;</div>
                    </div>
                </div>

                <div class="iph-person-section">
                    <div class="iph-person-title">Fiscal/Autoridad que recibe la puesta a disposición</div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Primer apellido:</div>
                        <div class="iph-line-value">{{ $nombreAutoridadPartes['primer_apellido'] }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Segundo apellido:</div>
                        <div class="iph-line-value">{{ $nombreAutoridadPartes['segundo_apellido'] }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Nombre (s):</div>
                        <div class="iph-line-value">{{ $nombreAutoridadPartes['nombres'] }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Fiscalía/Autoridad:</div>
                        <div class="iph-line-value">{{ $fiscaliaAutoridadIph }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Cargo:</div>
                        <div class="iph-line-value">{{ $cargoAutoridadIph }}</div>
                    </div>
                    <div class="iph-line-row">
                        <div class="iph-line-label">Firma:</div>
                        <div class="iph-line-value">&nbsp;</div>
                    </div>
                </div>

                <div class="iph-seal-box">Sello de la institución/autoridad que recibe el formato IPH</div>
            </div>
        </section>

        <section class="iph-continuation force-next-page">
            <div class="iph-form-section">
                <div class="iph-section-caption">Sección 2. Primer respondiente.</div>
                <div class="iph-subbar">Apartado 2.1. Datos de identificación</div>

                <div class="iph-grid-3">
                    <div class="iph-grid-cell">
                        <div class="iph-big-value">{{ $nombreQuienPonePartes['primer_apellido'] }}</div>
                        <div class="iph-small-label">Primer apellido</div>
                    </div>
                    <div class="iph-grid-cell">
                        <div class="iph-big-value">{{ $nombreQuienPonePartes['segundo_apellido'] }}</div>
                        <div class="iph-small-label">Segundo apellido</div>
                    </div>
                    <div class="iph-grid-cell">
                        <div class="iph-big-value">{{ $nombreQuienPonePartes['nombres'] }}</div>
                        <div class="iph-small-label">Nombre (s)</div>
                    </div>
                </div>

                <div class="iph-help">
                    Seleccione con una "X" la institución a la que pertenece, así como la entidad federativa o municipio de adscripción.
                </div>
                <div class="iph-authority-grid">
                    <div>
                        <div class="iph-authority-row"><span class="iph-check"></span><span>Guardia Nacional</span></div>
                        <div class="iph-authority-row"><span class="iph-check"></span><span>Policía Federal Ministerial</span></div>
                    </div>
                    <div>
                        <div class="iph-authority-row"><span class="iph-check"></span><span>Policía Ministerial</span></div>
                        <div class="iph-authority-row"><span class="iph-check"></span><span>Policía Mando Único</span></div>
                        <div class="iph-authority-row"><span class="iph-check">X</span><span>Policía Estatal</span></div>
                    </div>
                    <div>
                        <div class="iph-authority-row"><span>Otra autoridad:</span><span class="iph-free-line"></span></div>
                    </div>
                </div>

                <div class="iph-responder-lines">
                    <div class="iph-responder-line">
                        <span>¿Cuál es su grado o cargo?</span>
                        <span class="iph-responder-value">{{ $cargoQuienPoneIph }}</span>
                    </div>
                    <div class="iph-responder-line">
                        <span>¿En qué unidad arribó al lugar de intervención?</span>
                        <span class="iph-responder-value">{{ $unidadArriboIph }}</span>
                    </div>
                </div>

                <div class="iph-count-row">
                    <span>¿Arribó más de un elemento al lugar de la intervención?</span>
                    <span>Sí</span>
                    <span class="iph-check"></span>
                    <span>¿Cuántos?</span>
                    <span class="iph-small-boxes">{!! $renderIphBoxes('001', 3) !!}</span>
                    <span>No</span>
                    <span class="iph-check">X</span>
                </div>
            </div>

            <div class="iph-form-section">
                <div class="iph-section-caption">Sección 3. Conocimiento del hecho y seguimiento de la actuación de la autoridad</div>
                <div class="iph-subbar">Apartado 3.1 Conocimiento del hecho por el primer respondiente</div>
                <div class="iph-help">¿Cómo se enteró del hecho?</div>
                <div class="iph-knowledge-grid">
                    <div class="iph-knowledge-option"><span>Denuncia</span><span class="iph-check"></span></div>
                    <div class="iph-knowledge-option"><span>Flagrancia</span><span class="iph-check"></span></div>
                    <div class="iph-knowledge-option"><span>Localización</span><span class="iph-check">{{ ! $tieneFolioC5 ? 'X' : '' }}</span></div>
                    <div class="iph-knowledge-option"><span>Mandamiento judicial</span><span class="iph-check"></span></div>
                    <div class="iph-knowledge-option"><span>Llamada de emergencia</span><span class="iph-check">{{ $tieneFolioC5 ? 'X' : '' }}</span></div>
                    <div class="iph-knowledge-option"><span>Descubrimiento</span><span class="iph-check"></span></div>
                    <div class="iph-knowledge-option"><span>Aportación</span><span class="iph-check"></span></div>
                </div>
                <div class="iph-911-row">
                    <strong>911 No.</strong>
                    <span class="iph-date-boxes">{!! $renderIphBoxes($tieneFolioC5 ? $folioC5Iph : '', 22) !!}</span>
                    <em>Solo en caso de contar con él.</em>
                </div>

                <div class="iph-subbar">Apartado 3.2 Seguimiento de la actuación de la autoridad</div>
                <div class="iph-help">Indique la fecha y hora en cada recuadro.</div>
                <div class="iph-time-boxes">
                    <div class="iph-time-card">
                        <div class="iph-time-title">Conocimiento del hecho</div>
                        <div class="iph-date-line">
                            <span>Fecha:</span>
                            <span>
                                {!! $renderIphBoxes($fechaHechoPartes['dia'] . $fechaHechoPartes['mes'] . $fechaHechoPartes['anio'], 8) !!}
                                <span class="iph-date-caption">DDMMAAAA</span>
                            </span>
                        </div>
                        <div class="iph-time-line">
                            <span>Hora:</span>
                            <span>
                                {!! $renderIphBoxes($horaHechoPartes['hora'], 2) !!}
                                <span class="iph-colon">:</span>
                                {!! $renderIphBoxes($horaHechoPartes['minuto'], 2) !!}
                                <span class="iph-date-caption">HH&nbsp;&nbsp;&nbsp;MM (24 horas)</span>
                            </span>
                        </div>
                    </div>
                    <div class="iph-time-card">
                        <div class="iph-time-title">Arribo al lugar</div>
                        <div class="iph-date-line">
                            <span>Fecha:</span>
                            <span>
                                {!! $renderIphBoxes($arriboPartes['dia'] . $arriboPartes['mes'] . $arriboPartes['anio'], 8) !!}
                                <span class="iph-date-caption">DDMMAAAA</span>
                            </span>
                        </div>
                        <div class="iph-time-line">
                            <span>Hora:</span>
                            <span>
                                {!! $renderIphBoxes($arriboPartes['hora'], 2) !!}
                                <span class="iph-colon">:</span>
                                {!! $renderIphBoxes($arriboPartes['minuto'], 2) !!}
                                <span class="iph-date-caption">HH&nbsp;&nbsp;&nbsp;MM (24 horas)</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="iph-form-section">
                <div class="iph-section-caption">Sección 4. Lugar de la intervención</div>
                <div class="iph-subbar">Apartado 4.1 Ubicación geográfica</div>
                <div class="iph-geo-lines">
                    <div class="iph-geo-row">
                        <span>Calle/Tramo carretero:</span>
                        <span class="iph-geo-value">{{ $calleHechoParte }}</span>
                    </div>
                    <div class="iph-geo-row three-col">
                        <span>No. exterior</span>
                        <span class="iph-geo-value">&nbsp;</span>
                        <span>No. interior:</span>
                        <span class="iph-geo-value">&nbsp;</span>
                        <span>Código Postal:</span>
                        <span class="iph-geo-value">{{ $codigoPostalIph }}</span>
                    </div>
                    <div class="iph-geo-row">
                        <span>Colonia/Localidad:</span>
                        <span class="iph-geo-value">{{ $coloniaHechoParte }}</span>
                    </div>
                    <div class="iph-geo-row">
                        <span>Municipio/Demarcación territorial:</span>
                        <span class="iph-geo-value">{{ $municipioIntervencionIph }}</span>
                    </div>
                    <div class="iph-geo-row">
                        <span>Entidad federativa:</span>
                        <span class="iph-geo-value">{{ $entidadFederativaIph }}</span>
                    </div>
                    <div class="iph-geo-row">
                        <span>Referencias:</span>
                        <span class="iph-geo-value">{{ $referenciasIntervencionIph }}</span>
                    </div>
                    <div class="iph-help">Anote las coordenadas geográficas</div>
                    <div class="iph-coordinate-row">
                        <span>Latitud</span>
                        <span class="iph-date-boxes">{!! $renderIphBoxes($latitudIph, max(8, mb_strlen($latitudIph, 'UTF-8'))) !!}</span>
                        <span>Longitud: -</span>
                        <span class="iph-date-boxes">{!! $renderIphBoxes($longitudIph, max(9, mb_strlen($longitudIph, 'UTF-8'))) !!}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">1. Datos del hecho</div>
            <div class="grid">
                <div class="field">
                    <span class="label">Folio C5i</span>
                    <div class="value">{{ $valor($hechoIph['folio_c5i'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Fecha</span>
                    <div class="value">{{ $valor($hechoIph['fecha'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Hora</span>
                    <div class="value">{{ $valor($hechoIph['hora'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Estatus</span>
                    <div class="value">{{ $valor($hechoIph['situacion'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Unidad</span>
                    <div class="value">{{ $valor($hechoIph['unidad_org_nombre'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Delegación</span>
                    <div class="value">{{ $valor($hechoIph['delegacion_nombre'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Perito / Patrullero</span>
                    <div class="value">{{ $valor($hechoIph['perito'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Unidad económica</span>
                    <div class="value">{{ $valor($hechoIph['unidad_numero_economico'] ?? null) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Tipo de hecho</span>
                    <div class="value">{{ $valor($hechoIph['tipo_hecho'] ?? null) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Colisión / Camino</span>
                    <div class="value">{{ $valor($hechoIph['colision_camino'] ?? null) }}</div>
                </div>
                <div class="field full">
                    <span class="label">Lugar del hecho</span>
                    <div class="value">{{ $valor($lugarHecho) }}</div>
                </div>
                <div class="field full">
                    <span class="label">Causas / Observaciones iniciales</span>
                    <div class="value">{{ $valor($hechoIph['causas'] ?? null) }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">2. Datos de la puesta a disposición</div>
            <div class="grid">
                <div class="field">
                    <span class="label">No. puesta</span>
                    <div class="value">{{ $valor($puesta['folio'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Tipo</span>
                    <div class="value">{{ $valor($puesta['tipo_puesta'] ?? null) }}</div>
                </div>
                <div class="field">
                    <span class="label">Motivo</span>
                    <div class="value">{{ $valor($puesta['motivo'] ?? 'HECHO DE TRÁNSITO TURNADO') }}</div>
                </div>
                <div class="field">
                    <span class="label">Oficio</span>
                    <div class="value">{{ $valor($puesta['oficio'] ?? ($hechoIph['oficio_mp'] ?? null)) }}</div>
                </div>
                <div class="field">
                    <span class="label">Fecha puesta</span>
                    <div class="value">{{ $valor($puesta['fecha_puesta'] ?? ($hechoIph['fecha'] ?? null)) }}</div>
                </div>
                <div class="field">
                    <span class="label">Hora puesta</span>
                    <div class="value">{{ $valor($puesta['hora_puesta'] ?? ($hechoIph['hora'] ?? null)) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Lugar puesta</span>
                    <div class="value">{{ $valor($puesta['lugar_puesta'] ?? $lugarHecho) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Policía que pone a disposición</span>
                    <div class="value">{{ $valor($puesta['nombre_policia'] ?? ($hechoIph['perito'] ?? null)) }}</div>
                </div>
                <div class="field wide">
                    <span class="label">Autoridad receptora / MP</span>
                    <div class="value">{{ $valor($puesta['autoridad_receptora'] ?? ($puesta['nombre_mp'] ?? null)) }}</div>
                </div>
                <div class="field full">
                    <span class="label">Narrativa</span>
                    <div class="value">{{ $valor($puesta['narrativa'] ?? null, '') }}</div>
                </div>
                <div class="field full">
                    <span class="label">Observaciones</span>
                    <div class="value">{{ $valor($puesta['observaciones'] ?? null, '') }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section-title">3. Personas relacionadas</div>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Calidad</th>
                        <th>Edad</th>
                        <th>Sexo</th>
                        <th>Domicilio / Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($personasPuesta as $persona)
                        <tr>
                            <td>{{ $valor($persona['nombre_completo'] ?? null) }}</td>
                            <td>{{ $valor($persona['calidad'] ?? null) }}</td>
                            <td>{{ $valor($persona['edad'] ?? null) }}</td>
                            <td>{{ $valor($persona['sexo'] ?? null) }}</td>
                            <td>{{ $valor($persona['domicilio'] ?? null) }}<br>{{ $valor($persona['observaciones'] ?? null, '') }}</td>
                        </tr>
                    @empty
                        @forelse($conductoresHecho as $conductor)
                            <tr>
                                <td>{{ $valor($conductor['nombre'] ?? null) }}</td>
                                <td>CONDUCTOR</td>
                                <td>{{ $valor($conductor['edad'] ?? null) }}</td>
                                <td>{{ $valor($conductor['sexo'] ?? null) }}</td>
                                <td>{{ $valor($conductor['domicilio'] ?? null) }}<br>{{ $valor($conductor['vehiculo_label'] ?? null, '') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No hay personas mapeadas todavía.</td>
                            </tr>
                        @endforelse
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="section-title">4. Vehículos relacionados</div>
            <table>
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Placas</th>
                        <th>Serie</th>
                        <th>Color</th>
                        <th>Calidad / Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php $vehiculosTabla = !empty($vehiculosPuesta) ? $vehiculosPuesta : $vehiculosHecho; @endphp
                    @forelse($vehiculosTabla as $vehiculo)
                        <tr>
                            <td>{{ $valor(collect([$vehiculo['tipo'] ?? null, $vehiculo['marca'] ?? null, $vehiculo['submarca'] ?? ($vehiculo['linea'] ?? null), $vehiculo['modelo'] ?? null])->filter()->implode(' / ')) }}</td>
                            <td>{{ $valor($vehiculo['placas'] ?? null) }}</td>
                            <td>{{ $valor($vehiculo['serie'] ?? null) }}</td>
                            <td>{{ $valor($vehiculo['color'] ?? null) }}</td>
                            <td>{{ $valor($vehiculo['calidad'] ?? null, '') }} {{ $valor($vehiculo['observaciones'] ?? ($vehiculo['partes_danadas'] ?? null), '') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay vehículos mapeados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section">
            <div class="section-title">5. Objetos / indicios relacionados</div>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Cadena de custodia</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($objetosPuesta as $objeto)
                        <tr>
                            <td>{{ $valor($objeto['tipo_objeto'] ?? null) }}</td>
                            <td>{{ $valor($objeto['descripcion'] ?? null) }}</td>
                            <td>{{ $valor($objeto['cantidad'] ?? null) }} {{ $valor($objeto['unidad_medida'] ?? null, '') }}</td>
                            <td>{{ $valor($objeto['cadena_custodia'] ?? null) }}</td>
                            <td>{{ $valor($objeto['observaciones'] ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay objetos mapeados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div class="signatures">
            <div class="signature">Policía que pone a disposición</div>
            <div class="signature">Autoridad receptora</div>
            <div class="signature">Recibió</div>
        </div>

        <div class="hint">
            Formato generado desde el sistema para impresión y entrega física ante el MP.
        </div>
    </main>
</body>
</html>
