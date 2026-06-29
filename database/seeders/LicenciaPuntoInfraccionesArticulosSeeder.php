<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class LicenciaPuntoInfraccionesArticulosSeeder extends Seeder
{
    private const TABLE = 'licencia_punto_infracciones';
    private const SIN_LICENCIA_OPERATIVO_CODIGO = 'OP_CL_SIN_LICENCIA_SIN_HABILITADO';
    private const SIN_LICENCIA_OPERATIVO_NOMBRE = 'Persona sin licencia y sin persona habilitada inmediata';
    private const SIN_LICENCIA_OPERATIVO_FUNDAMENTO = 'Fundamento operativo compuesto: articulo 402, relativo a que solo puede conducir quien cuente con licencia vigente expedida por autoridad competente; articulos 700 y 702, relativos a supuestos expresos de retiro o remision al deposito. No se documenta como causal automatica "sin licencia = deposito"; se documenta que la persona carece de habilitacion juridica para conducir y que la circulacion no puede continuar bajo su mando. La medida de retiro se asienta solo cuando no existe en el lugar persona legalmente habilitada para hacerse cargo inmediato del vehiculo y se adopta para poner fin a la continuacion de la conducta.';

    public function run(): void
    {
        $this->validarTabla();
        $this->validarArchivoFuente();

        $now = now();
        $rows = $this->infracciones();

        $this->validarCodigosUnicos($rows);
        $this->desactivarRegistrosDePrueba($now);

        foreach ($rows as $row) {
            $exists = DB::table(self::TABLE)
                ->where('codigo', $row['codigo'])
                ->exists();

            DB::table(self::TABLE)->updateOrInsert(
                ['codigo' => $row['codigo']],
                array_merge($row, [
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }

        if ($this->command) {
            $this->command->info(count($rows) . ' fundamentos de articulos cargados en licencia_punto_infracciones.');
        }
    }

    private function validarTabla(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new RuntimeException('No existe la tabla ' . self::TABLE . '. Ejecuta primero las migrations.');
        }

        $faltantes = [];
        foreach ([
            'codigo',
            'nombre',
            'articulo',
            'fraccion',
            'inciso',
            'ambito_vehiculo',
            'puntos',
            'multa_uma_min',
            'multa_uma_max',
            'amonestacion',
            'arresto_persona',
            'suspension_licencia',
            'cancelacion_licencia',
            'deposito_si_sin_persona_habilitada',
            'retencion_vehiculo',
            'descripcion',
            'fundamento_legal',
            'activa',
        ] as $column) {
            if (!Schema::hasColumn(self::TABLE, $column)) {
                $faltantes[] = $column;
            }
        }

        if ($faltantes !== []) {
            throw new RuntimeException(
                'Faltan columnas en ' . self::TABLE . ': ' . implode(', ', $faltantes) . '. Ejecuta primero php artisan migrate.'
            );
        }
    }

    private function validarArchivoFuente(): void
    {
        $path = public_path('articulos.txt');

        if (!is_file($path)) {
            throw new RuntimeException('No se encontro el archivo fuente: public/articulos.txt.');
        }

        $contenido = file_get_contents($path);
        if ($contenido === false || trim($contenido) === '') {
            throw new RuntimeException('El archivo public/articulos.txt esta vacio o no se puede leer.');
        }

        $articulos = $this->articulosEnFuente($contenido);
        $faltantes = [];
        foreach ($this->infracciones() as $row) {
            if (($row['codigo'] ?? null) === self::SIN_LICENCIA_OPERATIVO_CODIGO) {
                continue;
            }

            foreach ($this->articulosReferenciados((string) ($row['articulo'] ?? '')) as $articulo) {
                if (!isset($articulos[$articulo])) {
                    $faltantes[] = $articulo;
                }
            }
        }

        if ($faltantes !== []) {
            throw new RuntimeException(
                'El archivo public/articulos.txt no contiene los articulos esperados: ' . implode(', ', array_values(array_unique($faltantes))) . '.'
            );
        }
    }

    private function articulosReferenciados(string $articuloRaw): array
    {
        return collect(preg_split('/[^0-9]+/', $articuloRaw) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function articulosEnFuente(string $contenido): array
    {
        preg_match_all('/^\s*Art[ií]culo\s+(\d+)\./imu', $contenido, $matches);

        return array_fill_keys($matches[1] ?? [], true);
    }

    private function desactivarRegistrosDePrueba($now): void
    {
        DB::table(self::TABLE)
            ->whereIn('codigo', [
                'EXCESO_VELOCIDAD',
                'CELULAR_CONDUCIR',
                'SEMAFORO_ROJO',
                'ART419_I_ABDE_SEGURIDAD',
            ])
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);
    }

    private function validarCodigosUnicos(array $rows): void
    {
        $seen = [];

        foreach ($rows as $row) {
            $codigo = $row['codigo'];
            if (isset($seen[$codigo])) {
                throw new RuntimeException('Codigo duplicado en seeder de articulos: ' . $codigo);
            }

            $seen[$codigo] = true;
        }
    }

    private function infracciones(): array
    {
        return [
            [
                'codigo' => self::SIN_LICENCIA_OPERATIVO_CODIGO,
                'nombre' => self::SIN_LICENCIA_OPERATIVO_NOMBRE,
                'articulo' => '402; 700; 702',
                'fraccion' => null,
                'inciso' => null,
                'ambito_vehiculo' => 'general',
                'puntos' => 0,
                'multa_uma_min' => null,
                'multa_uma_max' => null,
                'amonestacion' => false,
                'arresto_persona' => false,
                'deposito_si_sin_persona_habilitada' => false,
                'retencion_vehiculo' => true,
                'descripcion' => self::SIN_LICENCIA_OPERATIVO_NOMBRE,
                'fundamento_legal' => self::SIN_LICENCIA_OPERATIVO_FUNDAMENTO,
                'activa' => true,
            ],
            $this->row('402', null, null, 'LIC_FORANEA', 'Omitir registro de licencia expedida por otra entidad o extranjero', 0, 10, 30, false, false),
            $this->row('503', null, null, 'REG_VISITA_VENCIDO', 'Circular con registro de visita vencido', 0, 50, 60, true),
            $this->row('328', null, null, 'RETIRO_CIRCULACION', 'Supuestos generales de retiro de circulacion del vehiculo', 0, null, null, true, false),
            $this->row('328', 'II', null, 'LICENCIA_SUSPENDIDA_CANCELADA', 'Licencia suspendida o cancelada', 0, null, null, true, true, 'Aplica cuando la persona conductora tenga licencia suspendida o cancelada.'),

            $this->row('420', 'I', 'a,b,c,d,e', 'NO_MOTORIZADO_AMONESTACION', 'Conductas prohibidas en vehiculos no motorizados', 0, null, null, false, false, 'Amonestacion verbal.'),
            $this->row('420', 'II', 'a,b,g,p', 'MOTORIZADO_1P', 'Obstruir visibilidad, usar claxon innecesario o exceder pasajeros', 1, 20, 30),
            $this->row('420', 'II', 'c,n', 'MOTORIZADO_1P_B', 'Sostener personas u objetos o cruzar para estacionarse en sentido contrario', 1, 30, 40),
            $this->row('420', 'II', 'd,e,f,h,i,j,k,l,m,o,q,r,s', 'MOTORIZADO_3P', 'Distractores, celular, audio, sentido contrario, zigzagueo u otras conductas de riesgo', 3, 40, 50),
            $this->row('420', 'III', 'a,b', 'MOTO_CARGA_SUJETARSE', 'Motocicleta con carga que impide control o sujeta a otro vehiculo', 2, 20, 30),
            $this->row('420', 'III', 'c,d', 'MOTO_PASAJERO_MENOR', 'Motocicleta con pasajero entre conductor y manubrio o menor de doce anos', 6, 60, 80, true),
            $this->row('420', 'IV', 'a,b', 'TRANSPORTE_PUBLICO_ESCOLAR', 'Transporte publico, escolar o de personal con combustible o vidrios no permitidos', 6, 70, 80),
            $this->row('420', 'V', 'a', 'CARGA_PASAJEROS_AREA', 'Vehiculo de carga con pasajeros en area de carga', 3, 30, 50),
            $this->row('420', 'V', 'b', 'CARGA_EXCESIVA', 'Vehiculo de carga con exceso, obstruccion o sobresaliente sin permiso', 3, 60, 80),
            $this->row('420', 'VI', 'a', 'SUSTANCIAS_PERSONAS_AJENAS', 'Transporte de sustancias toxicas o peligrosas con personas ajenas', 3, 50, 60),
            $this->row('420', 'VI', 'b', 'SUSTANCIAS_DESCARGA', 'Arrojar, descargar o ventear sustancias toxicas o peligrosas', 6, 70, 80),

            $this->row('422', null, null, 'DOCUMENTOS_PLACAS_GENERAL', 'Incumplir obligaciones de placas, calcomania, tarjeta o registro temporal', 0, 30, 40, false, false),
            $this->row('422', 'I', 'c', 'PLACAS_NO_COINCIDEN', 'Placas o datos que no coinciden con calcomania, tarjeta o REV', 0, 30, 40, true),
            $this->row('425', null, null, 'USO_INDEBIDO_PLACAS_TARJETA', 'Usar tarjeta, placas, calcomanias u hologramas en vehiculo diverso', 0, 50, 60, true),

            $this->row('430', 'I', null, 'FUMAR_SUSTANCIAS_PELIGROSAS', 'Fumar en vehiculos que transportan sustancias explosivas o inflamables', 0, 130, 150, true, true, 'Suspension de la concesion por 60 dias.'),
            $this->row('430', 'II-VI', null, 'CONDUCTOR_CONDUCTAS_PROHIBIDAS', 'Conductor que saca partes del cuerpo, abre puertas, desciende en movimiento, obstaculiza o arroja objetos', 0, 20, 30, false, false),
            $this->row('430', 'II-VI', null, 'OPERADOR_CONDUCTAS_PROHIBIDAS', 'Operador que saca partes del cuerpo, abre puertas, desciende en movimiento, obstaculiza o arroja objetos', 0, 40, 60, false, false),

            $this->row('436', 'I', null, 'CONDUCTOR_CRUCE_PEATONAL', 'Conductor detenido sobre cruce peatonal o interseccion', 2, 20, 30),
            $this->row('436', 'I', null, 'OPERADOR_CRUCE_PEATONAL', 'Operador detenido sobre cruce peatonal o interseccion', 2, 25, 35),
            $this->row('436', 'II', null, 'CONDUCTOR_AREA_ESPERA', 'Conductor detenido sobre area de espera ciclista o motocicleta', 1, 15, 20),
            $this->row('436', 'II', null, 'OPERADOR_AREA_ESPERA', 'Operador detenido sobre area de espera ciclista o motocicleta', 2, 25, 35),
            $this->row('436', 'III', null, 'CONDUCTOR_AREA_RESTRINGIDA', 'Conductor circula o se detiene en areas restringidas, aceras o vias ciclistas', 3, 15, 20, true),
            $this->row('436', 'III', null, 'OPERADOR_AREA_RESTRINGIDA', 'Operador circula o se detiene en areas restringidas, aceras o vias ciclistas', 3, 25, 35, true),
            $this->row('436', 'IV', null, 'CONDUCTOR_SENALAMIENTO_RESTRICTIVO', 'Conductor se detiene donde existe senalamiento restrictivo o guarnicion amarilla', 3, 15, 20, true),
            $this->row('436', 'IV', null, 'OPERADOR_SENALAMIENTO_RESTRICTIVO', 'Operador se detiene donde existe senalamiento restrictivo o guarnicion amarilla', 3, 25, 35, true),
            $this->row('436', 'V', null, 'OBSTACULIZAR_COLUMNAS', 'Obstaculizar columnas militares, escolares, desfiles o cortejos funebres', 1, 10, 25),
            $this->row('436', 'VI', null, 'CONDUCTOR_REBASAR_CEDEN_PEATONES', 'Conductor rebasa vehiculos detenidos para ceder paso peatonal', 3, 30, 40),
            $this->row('436', 'VI', null, 'OPERADOR_REBASAR_CEDEN_PEATONES', 'Operador rebasa vehiculos detenidos para ceder paso peatonal', 3, 35, 45),
            $this->row('436', 'VII', null, 'MOVIMIENTO_CONTRARIO_SENALIZACION', 'Movimiento contrario a senalizacion vial en carriles de giro', 2, 10, 25),
            $this->row('436', 'VIII', null, 'CONDUCTOR_VUELTA_U', 'Conductor da vuelta en U cerca de curva o donde esta prohibido', 3, 30, 40),
            $this->row('436', 'VIII', null, 'OPERADOR_VUELTA_U', 'Operador da vuelta en U cerca de curva o donde esta prohibido', 3, 35, 45),
            $this->row('436', 'IX', null, 'CIRCULAR_ACOTAMIENTO', 'Circular sobre acotamiento salvo excepciones', 2, 10, 25),
            $this->row('436', 'X', 'a', 'CARRIL_CONFINADO_CIRCULAR', 'Circular en carriles confinados de transporte publico', 6, 50, 60),
            $this->row('436', 'X', 'b', 'CONDUCTOR_CARRIL_CONFINADO_ASCENSO', 'Conductor realiza ascenso, descenso, carga o descarga en carril confinado', 3, 20, 30),
            $this->row('436', 'X', 'b', 'OPERADOR_CARRIL_CONFINADO_ASCENSO', 'Operador realiza ascenso, descenso, carga o descarga en carril confinado', 4, 50, 65),
            $this->row('436', 'X', 'c', 'CARRIL_CONFINADO_ESTACIONARSE', 'Estacionarse o reparar vehiculo en carril confinado', 1, 10, 25),
            $this->row('436', 'X', 'd', 'OBSTACULIZAR_CARRIL_CONFINADO', 'Obstaculizar carriles confinados al dar vuelta o cambiar de cuerpo de circulacion', 4, 50, 60),
            $this->row('436', 'XI', null, 'CONDUCTOR_ASCENSO_CARRILES_CENTRALES', 'Conductor realiza ascenso o descenso en carriles centrales', 3, 40, 50),
            $this->row('436', 'XI', null, 'OPERADOR_ASCENSO_CARRILES_CENTRALES', 'Operador realiza ascenso o descenso en carriles centrales', 4, 70, 80),
            $this->row('436', 'XII', null, 'REBASAR_SENTIDO_CONTRARIO', 'Rebasar por carril de sentido contrario en supuestos prohibidos', 4, 50, 60),
            $this->row('436', 'XIII', null, 'CONDUCTOR_REVERSA', 'Conductor circula en reversa mas de treinta metros', 3, 15, 20),
            $this->row('436', 'XIII', null, 'OPERADOR_REVERSA', 'Operador circula en reversa mas de treinta metros', 3, 25, 35),
            $this->row('436', 'XIV', null, 'CONDUCTOR_VEHICULO_EMERGENCIA', 'Conductor circula detras de vehiculo de emergencia sin guardar distancia', 3, 40, 50),
            $this->row('436', 'XIV', null, 'OPERADOR_VEHICULO_EMERGENCIA', 'Operador circula detras de vehiculo de emergencia sin guardar distancia', 3, 50, 60),
            $this->row('436', 'XV', null, 'CONDUCTOR_OBSTRUIR_EMERGENCIAS', 'Conductor se detiene a distancia que entorpece atencion de emergencias', 3, 40, 50),
            $this->row('436', 'XV', null, 'OPERADOR_OBSTRUIR_EMERGENCIAS', 'Operador se detiene a distancia que entorpece atencion de emergencias', 3, 50, 60),
            $this->row('436', 'XVI', null, 'CARGAR_COMBUSTIBLE_MOTOR', 'Cargar combustible con el motor en marcha', 2, 20, 30),
            $this->row('436', 'XVII', null, 'EMPUJAR_REMOLCAR_SIN_GRUA', 'Empujar o remolcar vehiculos motorizados sin grua fuera de excepciones', 2, 15, 25),

            $this->row('440', 'I', null, 'MOTO_ACERAS_PEATONES', 'Motocicleta circula sobre aceras o areas peatonales', 4, 30, 40),
            $this->row('440', 'II', null, 'MOTO_VIA_CICLISTA', 'Motocicleta circula por vias exclusivas para ciclistas', 3, 30, 40, true),
            $this->row('440', 'III', null, 'MOTO_CARRIL_TRANSPORTE', 'Motocicleta circula por carriles confinados de transporte publico', 3, 20, 30),
            $this->row('440', 'IV', null, 'MOTO_PUENTE_PEATONAL', 'Motocicleta circula sobre puentes peatonales', 4, 20, 30),
            $this->row('440', 'V', null, 'MOTO_ENTRE_CARRILES', 'Motocicleta circula entre carriles fuera de excepciones', 1, 10, 20),
            $this->row('440', 'VI', null, 'MOTO_CARRILES_CENTRALES', 'Motocicleta circula por carriles centrales de acceso controlado', 3, 15, 25),
            $this->row('440', 'VII', null, 'MOTO_VIAS_RESTRINGIDAS', 'Motocicleta circula en vias primarias o restringidas por senalizacion', 3, 20, 30),
            $this->row('440', 'VIII', null, 'MOTO_MENORES_DOCE', 'Motocicleta lleva pasajeros menores de doce anos', 6, 60, 80),
            $this->row('440', 'IX', null, 'MOTO_MANIOBRAS_RIESGOSAS', 'Motocicleta realiza maniobras riesgosas o temerarias', 3, 40, 50),

            $this->row('465', 'I', null, 'SONIDO_EXCESIVO', 'Instalar o utilizar sonido con volumen excesivo', 3, 20, 30),
            $this->row('465', 'II', null, 'SIRENAS_TORRETAS', 'Instalar o utilizar sirenas, torretas, estrobos o codigos reservados', 6, 60, 80, true),
            $this->row('465', 'III', null, 'NEUMATICOS_METALICOS', 'Usar bandas de oruga, ruedas o neumaticos metalicos que danen la via', 6, 50, 70),
            $this->row('465', 'IV', null, 'FAROS_DESLUMBRANTES', 'Usar faros deslumbrantes fuera de NOM o riesgosos', 6, 50, 70),
            $this->row('465', 'V', null, 'PLACAS_OBSTRUIDAS_NEON', 'Luces de neon, portaplacas o micas que obstruyan placas', 4, 20, 30),
            $this->row('465', 'VI', null, 'ANTIRADARES', 'Instalar o utilizar sistemas antiradares o detectores de radares', 6, 60, 80, true),
            $this->row('465', 'VII', null, 'ESCAPE_RUIDO', 'Modificar sistema de escape para provocar ruido excesivo', 3, 30, 40),
            $this->row('465', 'VIII', null, 'ANUNCIOS_PUBLICITARIOS', 'Anuncios publicitarios no autorizados', 0, 10, 15, false, false),
            $this->row('465', 'IX', null, 'CLAXON_RUIDO_EXCESIVO', 'Bocina o claxon con ruido excesivo o sonido diverso al original', 3, 20, 30),
            $this->row('465', 'X', null, 'CROMATICA_PROHIBIDA', 'Vehiculo particular con cromatica similar a transporte publico o servicios oficiales', 6, 60, 80, true),
            $this->row('465', 'XI', null, 'POLARIZADO_MAYOR_20', 'Peliculas de control solar o polarizado mayor al veinte por ciento', 3, 30, 40, true),

            $this->row('475', null, null, 'PLACAS_FORANEAS_SIN_REGISTRO', 'Vehiculo con placas foraneas sin registro previo en REV', 0, 35, 40, true),
            $this->row('477', null, null, 'PLACAS_DEMOSTRACION_USO_INDEBIDO', 'Uso indebido de placas de demostracion', 3, 30, 40, true, true, 'La remision aplica cuando el uso sea en vehiculo no autorizado.'),
            $this->row('508', 'I-II', null, 'ALCOHOL_DROGAS_CONDUCTOR', 'Conductor con alcoholemia superior al limite o bajo influjo de sustancias', 3, 60, 80, true, true, 'La remision aplica si no hay otra persona apta para conducir.'),
            $this->row('508', 'III', null, 'INGERIR_BEBIDAS_VEHICULO', 'Ingerir bebidas embriagantes o sustancias en vehiculo automotor', 1, 30, 60),
            $this->row('508', 'IV', null, 'ALCOHOL_DROGAS_OPERADOR', 'Operador de transporte publico o especializado con alcohol o sustancias', 6, 90, 150, true),
            $this->row('519', 'IV', 'a', 'NO_MOVER_SINIESTRO_DANOS', 'No mover vehiculos cuando el siniestro solo ocasiona danos materiales y procede liberar vialidad', 6, 40, 50, true, true, 'La remision aplica cuando no este presente la persona conductora u operadora.'),
            $this->row('653', null, null, 'MENOR_EBRIEDAD_DROGAS', 'Persona menor de edad en estado de ebriedad o bajo influjo de sustancias', 0, null, null, true),
            $this->row('654', 'I', null, 'COMPETENCIAS_VELOCIDAD', 'Participar en competencias de velocidad en vias publicas', 0, null, null, true),
            $this->row('654', 'II', null, 'PLACAS_ROBADAS', 'Vehiculo porta placas reportadas como robadas', 0, null, null, true),
            $this->row('654', 'III', null, 'SUPUESTOS_ART328', 'Otros supuestos de retiro previstos en el articulo 328 de la Ley', 0, null, null, true, false),
            $this->row('663', null, null, 'REMISION_PERITO', 'Supuestos en los que el Perito puede ordenar remision al deposito', 0, null, null, true, false),
            $this->row('664', null, null, 'DEPOSITO_GARANTIA_REPARACION', 'Ingreso a deposito para garantizar reparacion del dano en siniestros', 0, null, null, true, false),
            $this->row('510', null, null, 'DEVOLUCION_DEPOSITO', 'Requisitos para devolucion del vehiculo en depositos vehiculares', 0, null, null, false, false),
            $this->row('672', null, null, 'LIBERACION_VEHICULO', 'Liberacion y entrega de vehiculos en depositos concesionarios', 0, null, null, false, false),
            $this->row('676', null, null, 'NO_COBRO_SERVICIOS_GRUA', 'Supuestos en que no procede cobrar arrastre, remolque o deposito', 0, null, null, false, false),
            $this->row('702', 'I', null, 'MEDIDA_SEGURIDAD_RETIRO', 'Medida de seguridad de retiro de circulacion de vehiculo', 0, null, null, true, false),
            $this->row('704', null, null, 'PROCEDIMIENTO_SSP', 'Procedimiento SSP para licencias, quejas y concesiones de gruas', 0, null, null, false, false),
        ];
    }

    private function row(
        string $articulo,
        ?string $fraccion,
        ?string $inciso,
        string $slug,
        string $nombre,
        int $puntos,
        ?int $umaMin,
        ?int $umaMax,
        bool $retencion = false,
        ?bool $activa = null,
        ?string $extra = null
    ): array {
        $activa = $activa ?? ($puntos > 0 || $retencion);
        $amonestacion = $this->textoContiene($extra, 'AMONESTACION');
        $arresto = $this->textoContiene($extra, 'ARRESTO');
        $codigo = $this->codigo($articulo, $fraccion, $inciso, $slug);

        $payload = [
            'codigo' => $codigo,
            'nombre' => Str::limit($nombre, 150, ''),
            'articulo' => $articulo,
            'fraccion' => $fraccion,
            'inciso' => $inciso,
            'ambito_vehiculo' => $this->inferirAmbitoVehiculo($articulo, $fraccion, $slug, $nombre),
            'puntos' => $puntos,
            'multa_uma_min' => null,
            'multa_uma_max' => null,
            'amonestacion' => $amonestacion,
            'arresto_persona' => $arresto,
            'suspension_licencia' => false,
            'cancelacion_licencia' => false,
            'deposito_si_sin_persona_habilitada' => $arresto,
            'retencion_vehiculo' => $retencion,
            'descripcion' => $nombre,
            'fundamento_legal' => '',
            'activa' => $activa,
        ];

        $payload = array_merge($payload, $this->decretoGoberOverride($codigo));
        $payload['fundamento_legal'] = $this->fundamentoLegalDesdePayload($payload, $extra);

        return $payload;
    }

    private function decretoGoberOverride(string $codigo): array
    {
        $overrides = [
            'ART420_FII_I_MOTORIZADO_1P' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false],
            'ART420_FII_I_MOTORIZADO_1P_B' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false],
            'ART420_FII_I_MOTORIZADO_3P' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART420_FIII_I_MOTO_CARGA_SUJETARSE' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false],
            'ART420_FIII_I_MOTO_PASAJERO_MENOR' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'cancelacion_licencia' => false, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART420_FIV_I_TRANSPORTE_PUBLICO_ESCOLAR' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART420_FV_I_CARGA_PASAJEROS_AREA' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART420_FV_I_CARGA_EXCESIVA' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART420_FVI_I_SUSTANCIAS_PERSONAS_AJENAS' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART420_FVI_I_SUSTANCIAS_DESCARGA' => ['amonestacion' => false, 'arresto_persona' => false, 'cancelacion_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART422_DOCUMENTOS_PLACAS_GENERAL' => ['puntos' => 4, 'amonestacion' => true, 'arresto_persona' => false, 'retencion_vehiculo' => true],
            'ART422_FI_I_PLACAS_NO_COINCIDEN' => ['puntos' => 4, 'amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true, 'retencion_vehiculo' => true],
            'ART425_USO_INDEBIDO_PLACAS_TARJETA' => ['puntos' => 3, 'amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true, 'retencion_vehiculo' => true],
            'ART436_FIII_CONDUCTOR_AREA_RESTRINGIDA' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true, 'retencion_vehiculo' => true],
            'ART436_FIII_OPERADOR_AREA_RESTRINGIDA' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true, 'retencion_vehiculo' => true],
            'ART436_FIV_CONDUCTOR_SENALAMIENTO_RESTRICTIVO' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART436_FIV_OPERADOR_SENALAMIENTO_RESTRICTIVO' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART436_FX_I_CARRIL_CONFINADO_CIRCULAR' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART436_FX_I_CONDUCTOR_CARRIL_CONFINADO_ASCENSO' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART436_FX_I_OPERADOR_CARRIL_CONFINADO_ASCENSO' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART436_FX_I_OBSTACULIZAR_CARRIL_CONFINADO' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FI_MOTO_ACERAS_PEATONES' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FII_MOTO_VIA_CICLISTA' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true, 'retencion_vehiculo' => true],
            'ART440_FIII_MOTO_CARRIL_TRANSPORTE' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FIV_MOTO_PUENTE_PEATONAL' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FV_MOTO_ENTRE_CARRILES' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FVI_MOTO_CARRILES_CENTRALES' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART440_FVII_MOTO_VIAS_RESTRINGIDAS' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FVIII_MOTO_MENORES_DOCE' => ['amonestacion' => false, 'arresto_persona' => false, 'cancelacion_licencia' => true, 'deposito_si_sin_persona_habilitada' => false],
            'ART440_FIX_MOTO_MANIOBRAS_RIESGOSAS' => ['amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART465_FII_SIRENAS_TORRETAS' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART465_FV_PLACAS_OBSTRUIDAS_NEON' => ['puntos' => 4, 'amonestacion' => false, 'arresto_persona' => true, 'deposito_si_sin_persona_habilitada' => true],
            'ART465_FVI_ANTIRADARES' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART465_FX_CROMATICA_PROHIBIDA' => ['amonestacion' => false, 'arresto_persona' => false, 'suspension_licencia' => true, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART465_FXI_POLARIZADO_MAYOR_20' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
            'ART477_PLACAS_DEMOSTRACION_USO_INDEBIDO' => ['amonestacion' => true, 'arresto_persona' => false, 'deposito_si_sin_persona_habilitada' => false, 'retencion_vehiculo' => true],
        ];

        return $overrides[$codigo] ?? [];
    }

    private function codigo(string $articulo, ?string $fraccion, ?string $inciso, string $slug): string
    {
        $partes = ['ART' . $articulo];

        if ($fraccion) {
            $partes[] = 'F' . $fraccion;
        }

        if ($inciso) {
            $partes[] = 'I' . $inciso;
        }

        $partes[] = $slug;
        $codigo = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '_', Str::ascii(implode('_', $partes))));
        $codigo = trim($codigo, '_');

        if (strlen($codigo) <= 50) {
            return $codigo;
        }

        return substr($codigo, 0, 43) . '_' . strtoupper(substr(sha1($codigo), 0, 6));
    }

    private function fundamentoLegal(
        string $articulo,
        ?string $fraccion,
        ?string $inciso,
        int $puntos,
        ?int $umaMin,
        ?int $umaMax,
        bool $retencion,
        ?string $extra
    ): string {
        $partes = [$this->referenciaLegal($articulo, $fraccion, $inciso)];
        $sanciones = [];
        $amonestacion = $this->tieneUma($umaMin, $umaMax) || $this->textoContiene($extra, 'AMONESTACION');
        $arresto = $this->tieneUma($umaMin, $umaMax) || $this->textoContiene($extra, 'ARRESTO');

        if ($amonestacion) {
            $sanciones[] = 'amonestacion a la persona';
        }

        if ($arresto) {
            $sanciones[] = 'arresto de la persona';
        }

        if ($puntos > 0) {
            $sanciones[] = $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos') . ' de penalizacion en la licencia para conducir';
        }

        if ($retencion) {
            $sanciones[] = 'remision o retiro del vehiculo al deposito';
        } elseif ($arresto) {
            $sanciones[] = 'deposito del vehiculo cuando no se encuentre persona legalmente habilitada para hacerse cargo inmediato';
        }

        if ($sanciones !== []) {
            $partes[] = implode('; ', $sanciones) . '.';
        }

        if ($extra) {
            $partes[] = rtrim($extra, '.') . '.';
        }

        return implode(': ', array_filter($partes));
    }

    private function fundamentoLegalDesdePayload(array $payload, ?string $extra): string
    {
        $partes = [$this->referenciaLegal($payload['articulo'], $payload['fraccion'], $payload['inciso'])];
        $sanciones = [];

        if (!empty($payload['amonestacion'])) {
            $sanciones[] = 'amonestacion a la persona';
        }

        if (!empty($payload['arresto_persona'])) {
            $sanciones[] = 'arresto de la persona hasta por 36 horas';
        }

        if (!empty($payload['suspension_licencia'])) {
            $sanciones[] = 'suspension de la licencia o permiso para conducir';
        }

        if (!empty($payload['cancelacion_licencia'])) {
            $sanciones[] = 'cancelacion de la licencia o permiso para conducir';
        }

        $puntos = (int) ($payload['puntos'] ?? 0);
        if ($puntos > 0) {
            $sanciones[] = $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos') . ' de penalizacion en la licencia para conducir';
        }

        if (!empty($payload['retencion_vehiculo'])) {
            $sanciones[] = 'remision o retiro del vehiculo al deposito';
        } elseif (!empty($payload['deposito_si_sin_persona_habilitada'])) {
            $sanciones[] = 'deposito del vehiculo cuando no se encuentre persona legalmente habilitada para hacerse cargo inmediato';
        }

        if ($sanciones !== []) {
            $partes[] = implode('; ', $sanciones) . '.';
        }

        if ($extra) {
            $partes[] = rtrim($extra, '.') . '.';
        }

        return implode(': ', array_filter($partes));
    }

    private function referenciaLegal(string $articulo, ?string $fraccion, ?string $inciso): string
    {
        $partes = ['Articulo ' . $articulo];

        if ($fraccion) {
            $partes[] = 'fraccion ' . $fraccion;
        }

        if ($inciso) {
            $partes[] = (strpos($inciso, ',') !== false ? 'incisos ' : 'inciso ') . $inciso;
        }

        return implode(', ', $partes);
    }

    private function inferirAmbitoVehiculo(string $articulo, ?string $fraccion, string $slug, string $nombre): string
    {
        $texto = $this->normalizarTexto($articulo . ' ' . ($fraccion ?? '') . ' ' . $slug . ' ' . $nombre);

        if ($this->contieneFrase($texto, 'NO MOTORIZADO')) {
            return 'no_motorizado';
        }

        if ($articulo === '440' || ($articulo === '420' && $fraccion === 'III') || $this->contieneFrase($texto, 'MOTOCICLETA') || $this->contienePalabra($texto, 'MOTO')) {
            return 'motocicleta';
        }

        if (
            $this->contieneFrase($texto, 'SUSTANCIAS')
            || $this->contieneFrase($texto, 'TOXICAS')
            || $this->contieneFrase($texto, 'PELIGROSAS')
            || $this->contieneFrase($texto, 'INFLAMABLES')
            || $this->contieneFrase($texto, 'EXPLOSIVAS')
        ) {
            return 'sustancias_peligrosas';
        }

        if (($articulo === '420' && $fraccion === 'V') || $this->contienePalabra($texto, 'CARGA')) {
            return 'carga';
        }

        if (
            $this->contieneFrase($texto, 'TRANSPORTE PUBLICO')
            || $this->contienePalabra($texto, 'OPERADOR')
            || $this->contienePalabra($texto, 'OPERADORA')
            || $this->contieneFrase($texto, 'TRANSPORTE ESCOLAR')
            || $this->contieneFrase($texto, 'DE PERSONAL')
        ) {
            return 'transporte_publico';
        }

        if (
            $this->contieneFrase($texto, 'SINIESTRO')
            || $this->contienePalabra($texto, 'PERITO')
            || $this->contieneFrase($texto, 'REPARACION DEL DANO')
        ) {
            return 'siniestro';
        }

        if (
            $this->contienePalabra($texto, 'CONDUCTOR')
            || $this->contienePalabra($texto, 'CONDUCTORA')
            || $this->contieneFrase($texto, 'MOTORIZADO')
            || $this->contieneFrase($texto, 'AUTOMOTOR')
            || $this->contieneFrase($texto, 'PARTICULAR')
        ) {
            return 'automovil';
        }

        return 'general';
    }

    private function contieneFrase(string $texto, string $needle): bool
    {
        return str_contains($texto, $needle);
    }

    private function contienePalabra(string $texto, string $needle): bool
    {
        return preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $texto) === 1;
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = strtoupper(strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]));
        $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto) ?? $texto;

        return preg_replace('/\s+/', ' ', trim($texto)) ?? $texto;
    }

    private function tieneUma(?int $min, ?int $max): bool
    {
        return $min !== null || $max !== null;
    }

    private function textoContiene(?string $texto, string $needle): bool
    {
        if ($texto === null || trim($texto) === '') {
            return false;
        }

        $normalizado = strtoupper(strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]));

        return str_contains($normalizado, $needle);
    }

    private function multaUmaTexto(?int $min, ?int $max): string
    {
        if ($min && $max) {
            return $min === $max ? $min . ' UMAS' : $min . ' a ' . $max . ' UMAS';
        }

        if ($min) {
            return $min . ' UMAS';
        }

        return 'hasta ' . $max . ' UMAS';
    }
}
