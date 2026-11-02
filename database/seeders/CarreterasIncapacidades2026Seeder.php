<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CarreterasIncapacidades2026Seeder extends Seeder
{
    private const UNIDAD_SLUG = 'carreteras';
    private const TIPO_CLAVE = 'INCAPACIDAD';
    private const MARCADOR = 'CARGA INCAPACIDADES CARRETERAS 2026';

    public function run(): void
    {
        $filas = $this->filas();
        $ahora = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');

        DB::transaction(function () use ($filas, $ahora) {
            $unidadId = (int) DB::table('unidades')
                ->where('slug', self::UNIDAD_SLUG)
                ->value('id');

            if ($unidadId <= 0) {
                throw new RuntimeException('No existe la unidad de Carreteras.');
            }

            $tipoId = $this->asegurarTipoIncapacidad($ahora);
            [$resueltas, $coincidenciasPorNombre] = $this->resolverPersonal($filas, $unidadId);
            $this->validarFilas($resueltas);

            $insertadas = 0;
            $actualizadas = 0;
            $existentes = 0;

            foreach ($resueltas as $fila) {
                $existente = DB::table('personal_incidencias')
                    ->where('personal_id', $fila['personal_id'])
                    ->where('incidencia_tipo_id', $tipoId)
                    ->whereDate('fecha_inicio', $fila['inicio'])
                    ->whereDate('fecha_fin', $fila['fin'])
                    ->first();

                $payload = [
                    'folio' => $fila['serie'] ?: $fila['oficio'],
                    'motivo' => $fila['motivo'],
                    'observaciones' => $this->observaciones($fila),
                    'documento_id' => null,
                    'activo' => true,
                    'updated_at' => $ahora,
                ];

                if ($existente) {
                    if (Str::startsWith((string) ($existente->observaciones ?? ''), self::MARCADOR)) {
                        DB::table('personal_incidencias')
                            ->where('id', $existente->id)
                            ->update($payload);
                        $actualizadas++;
                    } else {
                        $existentes++;
                    }

                    continue;
                }

                $traslape = DB::table('personal_incidencias')
                    ->where('personal_id', $fila['personal_id'])
                    ->where('activo', true)
                    ->where('fecha_inicio', '<=', $fila['fin'])
                    ->where(function ($query) use ($fila) {
                        $query->whereNull('fecha_fin')
                            ->orWhere('fecha_fin', '>=', $fila['inicio']);
                    })
                    ->first(['id', 'fecha_inicio', 'fecha_fin']);

                if ($traslape) {
                    throw new RuntimeException(sprintf(
                        'La fila %d (%s) se traslapa con la incidencia existente #%d (%s a %s).',
                        $fila['row'],
                        $fila['nombre'],
                        $traslape->id,
                        $traslape->fecha_inicio,
                        $traslape->fecha_fin ?: 'abierta'
                    ));
                }

                DB::table('personal_incidencias')->insert(array_merge($payload, [
                    'personal_id' => $fila['personal_id'],
                    'incidencia_tipo_id' => $tipoId,
                    'fecha_inicio' => $fila['inicio'],
                    'fecha_fin' => $fila['fin'],
                    'hora_inicio' => null,
                    'hora_fin' => null,
                    'created_at' => $ahora,
                ]));

                $insertadas++;
            }

            $this->command?->info(sprintf(
                'Incapacidades Carreteras 2026: %d insertadas, %d actualizadas, %d ya existentes; %d personas; %d coincidencias resueltas por nombre.',
                $insertadas,
                $actualizadas,
                $existentes,
                count(array_unique(array_column($resueltas, 'personal_id'))),
                $coincidenciasPorNombre
            ));
        }, 3);
    }

    private function asegurarTipoIncapacidad(string $ahora): int
    {
        $tipoId = DB::table('incidencia_tipos')
            ->where(function ($query) {
                $query->whereRaw('UPPER(TRIM(clave)) = ?', [self::TIPO_CLAVE])
                    ->orWhereRaw('UPPER(TRIM(nombre)) = ?', [self::TIPO_CLAVE]);
            })
            ->value('id');

        if ($tipoId) {
            DB::table('incidencia_tipos')
                ->where('id', $tipoId)
                ->update([
                    'clave' => self::TIPO_CLAVE,
                    'nombre' => self::TIPO_CLAVE,
                    'categoria' => 'PERSONAL',
                    'activo' => true,
                    'updated_at' => $ahora,
                ]);

            return (int) $tipoId;
        }

        return (int) DB::table('incidencia_tipos')->insertGetId([
            'clave' => self::TIPO_CLAVE,
            'nombre' => self::TIPO_CLAVE,
            'categoria' => 'PERSONAL',
            'descuenta' => false,
            'requiere_documento' => false,
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    }

    private function resolverPersonal(array $filas, int $unidadId): array
    {
        $personal = DB::table('personals')
            ->where('unidad_id', $unidadId)
            ->whereNull('deleted_at')
            ->get(['id', 'curp', 'nombre', 'ap_paterno', 'ap_materno']);

        if ($personal->isEmpty()) {
            throw new RuntimeException('No hay personal activo cargado en la unidad de Carreteras.');
        }

        $porCurp = [];
        $porNombre = [];

        foreach ($personal as $persona) {
            $curp = $this->normalizarIdentificador((string) $persona->curp);
            if ($curp !== '') {
                $porCurp[$curp][] = $persona;
            }

            $nombreCompleto = trim(implode(' ', array_filter([
                $persona->ap_paterno,
                $persona->ap_materno,
                $persona->nombre,
            ], fn ($valor) => trim((string) $valor) !== '')));

            $porNombre[$this->firmaNombre($nombreCompleto)][] = $persona;
        }

        $errores = [];
        $resueltas = [];
        $coincidenciasPorNombre = 0;

        foreach ($filas as $fila) {
            $curp = $this->normalizarIdentificador($fila['curp']);
            $coincidencias = strlen($curp) === 18 ? ($porCurp[$curp] ?? []) : [];
            $metodo = 'CURP';

            if (count($coincidencias) !== 1) {
                $coincidencias = $porNombre[$this->firmaNombre($fila['nombre'])] ?? [];
                $metodo = 'nombre';
            }

            if (count($coincidencias) !== 1) {
                $errores[] = sprintf(
                    'fila %d: %s, CURP %s (%d coincidencias)',
                    $fila['row'],
                    $fila['nombre'],
                    $fila['curp'],
                    count($coincidencias)
                );
                continue;
            }

            if ($metodo === 'nombre') {
                $coincidenciasPorNombre++;
            }

            $fila['personal_id'] = (int) $coincidencias[0]->id;
            $resueltas[] = $fila;
        }

        if ($errores !== []) {
            throw new RuntimeException(
                "No se pudo resolver todo el personal de Carreteras:\n- "
                . implode("\n- ", array_slice($errores, 0, 25))
                . (count($errores) > 25 ? "\n- ..." : '')
            );
        }

        return [$resueltas, $coincidenciasPorNombre];
    }

    private function validarFilas(array $filas): void
    {
        if (count($filas) !== 153) {
            throw new RuntimeException('La carga debe contener exactamente 153 incapacidades.');
        }

        $claves = [];

        foreach ($filas as $fila) {
            $inicio = Carbon::createFromFormat('Y-m-d', $fila['inicio'])->startOfDay();
            $fin = Carbon::createFromFormat('Y-m-d', $fila['fin'])->startOfDay();
            $dias = $inicio->diffInDays($fin) + 1;

            if ($fin->lt($inicio) || $dias !== (int) $fila['dias']) {
                throw new RuntimeException(sprintf(
                    'Fechas o días inválidos en la fila %d.',
                    $fila['row']
                ));
            }

            $clave = implode('|', [
                $fila['personal_id'],
                $fila['inicio'],
                $fila['fin'],
            ]);

            if (isset($claves[$clave])) {
                throw new RuntimeException(sprintf(
                    'Periodo duplicado en las filas %d y %d.',
                    $claves[$clave],
                    $fila['row']
                ));
            }

            $claves[$clave] = $fila['row'];
        }
    }

    private function observaciones(array $fila): string
    {
        return implode('; ', array_filter([
            self::MARCADOR,
            'EXCEL FILA ' . $fila['row'],
            $fila['oficio'] ? 'OFICIO ' . $fila['oficio'] : null,
            $fila['serie'] ? 'SERIE ' . $fila['serie'] : null,
            'DIAS ' . $fila['dias'],
            $fila['observacion'] ?: null,
        ]));
    }

    private function normalizarIdentificador(string $valor): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim($valor), 'UTF-8')) ?? '';
    }

    private function firmaNombre(string $valor): string
    {
        $normalizado = Str::ascii(mb_strtoupper(trim($valor), 'UTF-8'));
        $normalizado = preg_replace('/[^A-Z0-9]+/', ' ', $normalizado) ?? '';
        $partes = array_values(array_filter(preg_split('/\s+/', trim($normalizado)) ?: []));
        sort($partes, SORT_STRING);

        return implode(' ', $partes);
    }

    private function filas(): array
    {
        return [
            ['row' => 2, 'curp' => 'MEZA820809MMNDMD03', 'nombre' => 'MEDINA ZAMUDIO ADRIANA', 'oficio' => '1417', 'serie' => 'UP634875', 'inicio' => '2025-12-19', 'fin' => '2026-01-09', 'dias' => 22, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 3, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '1418', 'serie' => 'WW571332', 'inicio' => '2025-12-18', 'fin' => '2025-12-20', 'dias' => 3, 'motivo' => null, 'observacion' => 'ENFERMEDAD GENERAL'],
            ['row' => 4, 'curp' => 'SAAG860212MMNLRL00', 'nombre' => 'SALCEDO ARISMENDI GLORIA', 'oficio' => '1434', 'serie' => 'PC065317', 'inicio' => '2025-12-22', 'fin' => '2025-12-26', 'dias' => 5, 'motivo' => null, 'observacion' => 'ENFERMEDAD GENERAL'],
            ['row' => 5, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALASAR LUID ENRIQUE', 'oficio' => '1442', 'serie' => 'UP635083', 'inicio' => '2025-12-29', 'fin' => '2026-01-25', 'dias' => 28, 'motivo' => null, 'observacion' => 'ENFERMEDAD GENERAL'],
            ['row' => 6, 'curp' => 'BAIA780827HVZRDR17', 'nombre' => 'BARTOLO IDAZA ARMANDO', 'oficio' => '0027', 'serie' => 'GP621967', 'inicio' => '2026-01-06', 'fin' => '2026-01-06', 'dias' => 1, 'motivo' => null, 'observacion' => 'ENFERMEDAD GENERAL'],
            ['row' => 7, 'curp' => 'MEAL980705MMNDGZ04', 'nombre' => 'MEDRANO AGUILERA LIZBETH', 'oficio' => '0035', 'serie' => 'OY057304', 'inicio' => '2026-01-07', 'fin' => '2026-01-09', 'dias' => 3, 'motivo' => null, 'observacion' => 'ENFERMEDAD GENERAL'],
            ['row' => 8, 'curp' => 'MEOJ630826HDFNRN03', 'nombre' => 'MENDOZA ORTIZ JUAN MIGUEL', 'oficio' => '0045', 'serie' => 'UP635417', 'inicio' => '2026-01-05', 'fin' => '2026-02-01', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 9, 'curp' => 'MEZA820809MMNDMD03', 'nombre' => 'MEDINA ZAMUDIO ADRIANA', 'oficio' => '0055', 'serie' => 'UP635520', 'inicio' => '2026-01-10', 'fin' => '2026-01-16', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 10, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSE MANUEL', 'oficio' => '0057', 'serie' => 'FJ812132', 'inicio' => '2026-01-06', 'fin' => '2026-01-08', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 11, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSE MANUEL', 'oficio' => '0058', 'serie' => 'FJ812339', 'inicio' => '2026-01-09', 'fin' => '2026-01-11', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 12, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSÉ MANUEL', 'oficio' => '0080', 'serie' => 'FJ812339', 'inicio' => '2026-01-12', 'fin' => '2026-01-14', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 13, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'MARIBEL CASTREJON ZAMUDIO', 'oficio' => '0121', 'serie' => null, 'inicio' => '2026-01-20', 'fin' => '2026-02-09', 'dias' => 21, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 14, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '0160', 'serie' => 'UP636001', 'inicio' => '2026-01-26', 'fin' => '2026-02-22', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 15, 'curp' => 'MEGR820109MMNNNB02', 'nombre' => 'MENDEZ GONZALEZ RUBI', 'oficio' => '0186', 'serie' => 'PC067973', 'inicio' => '2026-01-28', 'fin' => '2026-01-30', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 16, 'curp' => 'MEGR820109MMNNNB02', 'nombre' => 'MÉNDEZ GONZÁLEZ RUBÍ', 'oficio' => '0198', 'serie' => 'PC068175', 'inicio' => '2026-01-31', 'fin' => '2026-02-04', 'dias' => 5, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 17, 'curp' => 'MEOJ630826HDFNRN03', 'nombre' => 'MENDOZA ORTIZ JUAN MIGUEL', 'oficio' => '216', 'serie' => 'UP636239', 'inicio' => '2026-02-02', 'fin' => '2026-03-01', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 18, 'curp' => 'MEGR820109MMNNNB02', 'nombre' => 'MÉNDEZ GONZÁLEZ RUBÍ', 'oficio' => '217', 'serie' => 'PC068405', 'inicio' => '2026-02-05', 'fin' => '2026-02-06', 'dias' => 2, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 19, 'curp' => 'MARC970321MMNRNH06', 'nombre' => 'MARTINEZ RANGEL CHRISTIAN', 'oficio' => '0236', 'serie' => 'GP623729', 'inicio' => '2026-02-04', 'fin' => '2026-02-05', 'dias' => 2, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 20, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '0464', 'serie' => 'SG210844', 'inicio' => '2026-02-02', 'fin' => '2026-03-01', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 21, 'curp' => 'SAAG860212MMNLRL00', 'nombre' => 'SALCEDO ARISMENDI GLORIA', 'oficio' => '0466', 'serie' => 'SF900477', 'inicio' => '2026-02-11', 'fin' => '2026-03-03', 'dias' => 21, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 22, 'curp' => 'VEPC880101HDFLRR06', 'nombre' => 'VELAZQUEZ PEREZ CRUZ ALBERTO', 'oficio' => '0478', 'serie' => 'GP624223', 'inicio' => '2026-02-12', 'fin' => '2026-02-18', 'dias' => 7, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 23, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSE MANUEL', 'oficio' => '0534', 'serie' => 'HE401645', 'inicio' => '2026-02-08', 'fin' => '2026-03-02', 'dias' => 23, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 24, 'curp' => 'VEPC880101HDFLRR06', 'nombre' => 'VELAZQUEZ PEREZ CRUZ ALBERTO', 'oficio' => '0543', 'serie' => 'GP624631', 'inicio' => '2026-02-19', 'fin' => '2026-03-04', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 25, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '0569', 'serie' => 'UP636890', 'inicio' => '2026-02-23', 'fin' => '2026-03-22', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 26, 'curp' => 'MEOJ630826HDFNRN03', 'nombre' => 'MENDOZA ORTIZ JUAN MIGUEL', 'oficio' => '0639', 'serie' => 'UP637151', 'inicio' => '2026-03-02', 'fin' => '2026-03-29', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 27, 'curp' => 'SAAG860212MMNLRL00', 'nombre' => 'SALCEDO ARISMENDI GLORIA', 'oficio' => '0648', 'serie' => 'PC070592', 'inicio' => '2026-03-04', 'fin' => '2026-03-10', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 28, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSE MANUEL', 'oficio' => '0658', 'serie' => 'FJ815147', 'inicio' => '2026-03-03', 'fin' => '2026-03-06', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 29, 'curp' => 'VEPC880101HDFLRR06', 'nombre' => 'VELAZQUEZ PEREZ CRUZ ALBERTO', 'oficio' => '0663', 'serie' => 'GP625451', 'inicio' => '2026-03-05', 'fin' => '2026-03-11', 'dias' => 7, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 30, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '0704', 'serie' => 'SG211310', 'inicio' => '2026-03-02', 'fin' => '2026-03-29', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 31, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSE MANUEL', 'oficio' => '0705', 'serie' => 'HE800735', 'inicio' => '2026-03-07', 'fin' => '2026-03-21', 'dias' => 15, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 32, 'curp' => 'AEHM891002HOCCRT06', 'nombre' => 'ACEVEDO HERNANDEZ MATIAS', 'oficio' => '0713', 'serie' => 'GP625623', 'inicio' => '2026-03-09', 'fin' => '2026-03-09', 'dias' => 1, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 33, 'curp' => 'AEHM891002HOCCRT06', 'nombre' => 'ACEVEDO HERNANDEZ MATIAS', 'oficio' => '0714', 'serie' => 'GP625673', 'inicio' => '2026-03-10', 'fin' => '2026-03-13', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 34, 'curp' => 'VEPC880101HDFLRR06', 'nombre' => 'VELAZQUEZ PEREZ CRUZ ALBERTO', 'oficio' => '0780', 'serie' => 'GP626214', 'inicio' => '2026-03-19', 'fin' => '2026-03-25', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 35, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '0800', 'serie' => 'UP637861', 'inicio' => '2026-03-23', 'fin' => '2026-04-19', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 36, 'curp' => 'SITM770221HMCLPN02', 'nombre' => 'SILVA TAPIA JOSE MANUEL', 'oficio' => '0802', 'serie' => 'HE800889', 'inicio' => '2026-03-22', 'fin' => '2026-03-28', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 37, 'curp' => 'VILE871129MMNLPS01', 'nombre' => 'VILLANUEVA LOPEZ ESMERALDA', 'oficio' => '0816', 'serie' => 'PC072270', 'inicio' => '2026-03-26', 'fin' => '2026-03-27', 'dias' => 2, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 38, 'curp' => 'VEPC880101HDFLRR06', 'nombre' => 'VELAZQUEZ PEREZ CRUZ ALBERTO', 'oficio' => '0835', 'serie' => 'GP626669', 'inicio' => '2026-03-26', 'fin' => '2026-04-01', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 39, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '0863', 'serie' => 'SG211737', 'inicio' => '2026-03-30', 'fin' => '2026-04-26', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 40, 'curp' => 'ROMV011205MMDNNA3', 'nombre' => 'RODRÍGUEZ MONDRAGÓN VANESSA EVELYN', 'oficio' => '0904', 'serie' => 'SG113747', 'inicio' => '2026-04-03', 'fin' => '2026-04-05', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 41, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '0977', 'serie' => 'UP638738', 'inicio' => '2026-04-20', 'fin' => '2026-05-17', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 42, 'curp' => 'RURG820917MMNBVB07', 'nombre' => 'RUBALCAVA RIVERA GABRIELA', 'oficio' => '1018', 'serie' => 'PC074229', 'inicio' => '2026-04-23', 'fin' => '2026-04-26', 'dias' => 4, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 43, 'curp' => 'RURG820917MMNBVB07', 'nombre' => 'RUBALCAVA RIVERA GABRIELA', 'oficio' => '1043', 'serie' => 'SG212369', 'inicio' => '2026-04-28', 'fin' => '2026-05-07', 'dias' => 10, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 44, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '1084', 'serie' => 'SG212443', 'inicio' => '2026-04-27', 'fin' => '2026-05-24', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 45, 'curp' => 'RURG820917MMNBVB07', 'nombre' => 'RUBALCAVA RIVERA GABRIELA', 'oficio' => '1150', 'serie' => 'SG212664', 'inicio' => '2026-05-12', 'fin' => '2026-05-21', 'dias' => 10, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 46, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1207', 'serie' => 'UP639696', 'inicio' => '2026-05-18', 'fin' => '2026-06-09', 'dias' => 23, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 47, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1125', 'serie' => 'SG114974', 'inicio' => '2026-05-15', 'fin' => '2026-05-16', 'dias' => 2, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 48, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1126', 'serie' => 'GP629426', 'inicio' => '2026-05-17', 'fin' => '2026-05-30', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 49, 'curp' => 'AARM730623HOCLJG00', 'nombre' => 'ALFARO ROJAS MIGUEL', 'oficio' => '1232', 'serie' => 'UU934871', 'inicio' => '2026-05-20', 'fin' => '2026-05-26', 'dias' => 7, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 50, 'curp' => 'RURG820917MMNBVB07', 'nombre' => 'RUBALCAVA RIVERA GABRIELA', 'oficio' => '1274', 'serie' => 'SG213020', 'inicio' => '2026-05-25', 'fin' => '2026-06-03', 'dias' => 10, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 51, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '1285', 'serie' => 'SG213098', 'inicio' => '2026-05-25', 'fin' => '2026-06-21', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 52, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1330', 'serie' => 'GP630380', 'inicio' => '2026-05-31', 'fin' => '2026-06-06', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 53, 'curp' => 'HUIS000925HMNRBLA5', 'nombre' => 'HUERTA IBARRA SALVADOR', 'oficio' => '1338', 'serie' => 'OY069964', 'inicio' => '2026-05-29', 'fin' => '2026-05-29', 'dias' => 1, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 54, 'curp' => 'SAAG860212MMNLRL00', 'nombre' => 'SALCEDO ARISMENDI GLORIA', 'oficio' => '1377', 'serie' => 'PC077353', 'inicio' => '2026-06-08', 'fin' => '2026-06-09', 'dias' => 2, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 55, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1380', 'serie' => 'GP630883', 'inicio' => '2026-06-07', 'fin' => '2026-06-20', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 56, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '1384', 'serie' => 'UR145187', 'inicio' => '2026-06-01', 'fin' => '2026-06-03', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 57, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '1385', 'serie' => 'UR145580', 'inicio' => '2026-06-04', 'fin' => '2026-06-25', 'dias' => 22, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 58, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1397', 'serie' => 'GP631131', 'inicio' => '2026-06-10', 'fin' => '2026-06-16', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 59, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1470', 'serie' => 'GP631538', 'inicio' => '2026-06-17', 'fin' => '2026-06-19', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 60, 'curp' => 'RARJ951028HDFMML00', 'nombre' => 'RAMÍREZ ROMÁN JULIO ALBERTO', 'oficio' => '1475', 'serie' => 'OY071880', 'inicio' => '2026-06-18', 'fin' => '2026-06-19', 'dias' => 2, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 61, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1481', 'serie' => 'GP631784', 'inicio' => '2026-06-20', 'fin' => '2026-06-21', 'dias' => 2, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 62, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '1494', 'serie' => 'SG213624', 'inicio' => '2026-06-22', 'fin' => '2026-07-19', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 63, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1510', 'serie' => 'GP631916', 'inicio' => '2026-06-21', 'fin' => '2026-06-24', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 64, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1514', 'serie' => 'GP631899', 'inicio' => '2026-06-22', 'fin' => '2026-06-28', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 65, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1521', 'serie' => 'SG213708', 'inicio' => '2026-06-25', 'fin' => '2026-07-09', 'dias' => 15, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 66, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1555', 'serie' => 'UP641204', 'inicio' => '2026-06-29', 'fin' => '2026-07-26', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 67, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '1572', 'serie' => 'WW573642', 'inicio' => '2026-06-27', 'fin' => '2026-06-29', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 68, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '1599', 'serie' => 'QB074901', 'inicio' => '2026-06-26', 'fin' => '2026-07-16', 'dias' => 21, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 69, 'curp' => 'LALG980318MMNRMR01', 'nombre' => 'LARA LEMUS GRECIA FERNANDA', 'oficio' => '1602', 'serie' => 'OY073540', 'inicio' => '2026-07-06', 'fin' => '2026-07-08', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 70, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '1612', 'serie' => 'WW573752', 'inicio' => '2026-07-05', 'fin' => '2026-07-05', 'dias' => 1, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 71, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '1613', 'serie' => 'WW573759', 'inicio' => '2026-07-06', 'fin' => '2026-07-10', 'dias' => 5, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 72, 'curp' => 'LALG980318MMNRMR01', 'nombre' => 'LARA LEMUS GRECIA FERNANDA', 'oficio' => '1627', 'serie' => 'OY073881', 'inicio' => '2026-07-09', 'fin' => '2026-07-09', 'dias' => 1, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 73, 'curp' => 'MOMF710928MDFLNN07', 'nombre' => 'MOLINA MENDOZA FANNY PATRICIA', 'oficio' => '1634', 'serie' => 'OY073892', 'inicio' => '2026-07-09', 'fin' => '2026-07-12', 'dias' => 4, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 74, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUAREZ JORGE', 'oficio' => '1635', 'serie' => 'UP641075', 'inicio' => '2026-06-07', 'fin' => '2026-06-07', 'dias' => 1, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 75, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUAREZ JORGE', 'oficio' => '1636', 'serie' => 'UP640835', 'inicio' => '2026-06-08', 'fin' => '2026-07-05', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 76, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJÓN ZAMUDIO MARIBEL', 'oficio' => '1642', 'serie' => 'WW573847', 'inicio' => '2026-07-11', 'fin' => '2026-07-17', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 77, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1643', 'serie' => 'SH909291', 'inicio' => '2026-07-11', 'fin' => '2026-07-12', 'dias' => 2, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 78, 'curp' => 'COLB010630HMNRMRA9', 'nombre' => 'CORONA LEMUS BRAYAN ANDRES', 'oficio' => '1664', 'serie' => 'GP633062', 'inicio' => '2026-07-10', 'fin' => '2026-07-13', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 79, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '1696', 'serie' => 'WW573927', 'inicio' => '2026-07-18', 'fin' => '2026-07-18', 'dias' => 1, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 80, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '1697', 'serie' => 'WW573946', 'inicio' => '2026-07-19', 'fin' => '2026-07-24', 'dias' => 6, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 81, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1718', 'serie' => 'UH197255', 'inicio' => '2026-07-20', 'fin' => '2026-07-26', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 82, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '1719', 'serie' => 'SN230832', 'inicio' => '2026-07-17', 'fin' => '2026-08-05', 'dias' => 20, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 83, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '1759', 'serie' => 'SG214472', 'inicio' => '2026-07-20', 'fin' => '2026-08-16', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 84, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1779', 'serie' => 'UH197386', 'inicio' => '2026-07-27', 'fin' => '2026-07-30', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 85, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1783', 'serie' => 'GP633914', 'inicio' => '2026-07-27', 'fin' => '2026-08-09', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 86, 'curp' => 'HUIS000925HMNRBLA5', 'nombre' => 'HUERTA IBARRA SALVADOR', 'oficio' => '1804', 'serie' => 'OY07587', 'inicio' => '2026-07-31', 'fin' => '2026-08-01', 'dias' => 2, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 87, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1854', 'serie' => 'UH197480', 'inicio' => '2026-07-31', 'fin' => '2026-08-03', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 88, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1855', 'serie' => 'UH197543', 'inicio' => '2026-08-04', 'fin' => '2026-08-07', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 89, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUAREZ JORGE', 'oficio' => '1866', 'serie' => 'UP642023', 'inicio' => '2026-07-06', 'fin' => '2026-08-02', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 90, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUAREZ JORGE', 'oficio' => '1867', 'serie' => 'UP642489', 'inicio' => '2026-08-03', 'fin' => '2026-08-30', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 91, 'curp' => 'ROHG950710HMNDRS09', 'nombre' => 'RODRÍGUEZ HERRERA JOSÉ GUSTAVO', 'oficio' => '1869', 'serie' => 'SG117590', 'inicio' => '2026-08-08', 'fin' => '2026-08-10', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 92, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '1871', 'serie' => 'GP634611', 'inicio' => '2026-08-10', 'fin' => '2026-08-23', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 93, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1886', 'serie' => 'UH197629', 'inicio' => '2026-08-08', 'fin' => '2026-08-11', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 94, 'curp' => 'HUIS000925HMNRBLA5', 'nombre' => 'HUERTA IBARRA SALVADOR', 'oficio' => '1889', 'serie' => 'OY076795', 'inicio' => '2026-08-10', 'fin' => '2026-08-14', 'dias' => 5, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 95, 'curp' => 'ROHG950710HMNDRS09', 'nombre' => 'RODRÍGUEZ HERRERA JOSÉ GUSTAVO', 'oficio' => '1897', 'serie' => 'PZ059036', 'inicio' => '2026-08-11', 'fin' => '2026-08-17', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 96, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '1928', 'serie' => 'UR153336', 'inicio' => '2026-08-07', 'fin' => '2026-08-11', 'dias' => 5, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 97, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '1929', 'serie' => 'QB077898', 'inicio' => '2026-08-12', 'fin' => '2026-09-08', 'dias' => 28, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 98, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1930', 'serie' => 'UH197699', 'inicio' => '2026-08-12', 'fin' => '2026-08-15', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 99, 'curp' => 'POHJ820723HDFNNN05', 'nombre' => 'PONCE HINOJOSA JUAN CARLOS', 'oficio' => '1947', 'serie' => 'VE679831', 'inicio' => '2026-08-13', 'fin' => '2026-08-19', 'dias' => 7, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 100, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '1948', 'serie' => 'SH11119', 'inicio' => '2026-08-16', 'fin' => '2026-08-25', 'dias' => 10, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 101, 'curp' => 'JIYJ871217HMNMPV09', 'nombre' => 'JIMÉNEZ YEPEZ JAVIER', 'oficio' => '1954', 'serie' => 'UH197775', 'inicio' => '2026-08-17', 'fin' => '2026-08-19', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 102, 'curp' => 'ROHG950710HMNDRS09', 'nombre' => 'RODRÍGUEZ HERRERA JOSÉ GUSTAVO', 'oficio' => '1964', 'serie' => 'PZ059522', 'inicio' => '2026-08-18', 'fin' => '2026-08-31', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 103, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '1966', 'serie' => 'PC082663', 'inicio' => '2026-08-17', 'fin' => '2026-08-23', 'dias' => 7, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 104, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '2001', 'serie' => 'SH909842', 'inicio' => '2026-08-20', 'fin' => '2026-08-22', 'dias' => 3, 'motivo' => null, 'observacion' => 'INICIAL'],
            ['row' => 105, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '2002', 'serie' => 'WW574389', 'inicio' => '2026-08-23', 'fin' => '2026-08-25', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 106, 'curp' => 'JIYJ871217HMNMPV09', 'nombre' => 'JIMÉNEZ YEPEZ JAVIER', 'oficio' => '2003', 'serie' => 'UH197858', 'inicio' => '2026-08-20', 'fin' => '2026-08-23', 'dias' => 4, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 107, 'curp' => 'AOMI770416HDFRRS00', 'nombre' => 'AROUESTY MARTINEZ ISRAEL ROBERTO', 'oficio' => '2017', 'serie' => 'PC082996', 'inicio' => '2026-08-24', 'fin' => '2026-08-26', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 108, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '2023', 'serie' => 'GP635522', 'inicio' => '2026-08-24', 'fin' => '2026-09-06', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 109, 'curp' => 'JIYJ871217HMNMPV09', 'nombre' => 'JIMÉNEZ YEPEZ JAVIER', 'oficio' => '2030', 'serie' => 'UH197933', 'inicio' => '2026-08-24', 'fin' => '2026-08-26', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 110, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '2031', 'serie' => 'WW574426', 'inicio' => '2026-08-26', 'fin' => '2026-08-28', 'dias' => 3, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 111, 'curp' => 'JIMA020817MMNMRRA9', 'nombre' => 'JIMÉNEZ MORA ARCELIA DENISSE', 'oficio' => '2038', 'serie' => 'OP009680', 'inicio' => '2026-08-28', 'fin' => '2026-08-30', 'dias' => 3, 'motivo' => 'INTERRUPCION DE EMBARAZO', 'observacion' => 'INICIAL'],
            ['row' => 112, 'curp' => 'RORA670307HGRDYL08', 'nombre' => 'RODRIGUEZ RAYO ALEJANDRO', 'oficio' => '2044', 'serie' => 'MJ146723', 'inicio' => '2026-08-25', 'fin' => '2026-08-25', 'dias' => 1, 'motivo' => 'LESIÓN DE TENDÓN', 'observacion' => 'INICIAL'],
            ['row' => 113, 'curp' => 'RORA670307HGRDYL08', 'nombre' => 'RODRIGUEZ RAYO ALEJANDRO', 'oficio' => '2045', 'serie' => 'MJ147102', 'inicio' => '2026-08-26', 'fin' => '2026-09-22', 'dias' => 28, 'motivo' => 'LESIÓN DE TENDÓN', 'observacion' => 'SUBSECUENTE'],
            ['row' => 114, 'curp' => 'RURG820917MMNBVB07', 'nombre' => 'RUBALCAVA RIVERA GABRIELA', 'oficio' => '2050', 'serie' => 'SG215319', 'inicio' => '2026-08-30', 'fin' => '2026-09-26', 'dias' => 28, 'motivo' => 'CIRUGIA GENERAL', 'observacion' => 'INICIAL'],
            ['row' => 115, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '2057', 'serie' => 'WW574464', 'inicio' => '2026-08-29', 'fin' => '2026-08-31', 'dias' => 3, 'motivo' => 'BURSITIS DE RODILLA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 116, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '2058', 'serie' => 'SH811230', 'inicio' => '2026-08-26', 'fin' => '2026-09-15', 'dias' => 21, 'motivo' => 'HERIDA POR ACCIDENTE EN MOTO', 'observacion' => 'INICIAL'],
            ['row' => 117, 'curp' => 'ROHG950710HMNDRS09', 'nombre' => 'RODRÍGUEZ HERRERA JOSÉ GUSTAVO', 'oficio' => '2074', 'serie' => 'PZ060469', 'inicio' => '2026-09-01', 'fin' => '2026-09-05', 'dias' => 5, 'motivo' => 'FRACTURA DE CLAVÍCULA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 118, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUÁREZ JORGE', 'oficio' => '2086', 'serie' => 'GP635923', 'inicio' => '2026-09-01', 'fin' => '2026-09-14', 'dias' => 14, 'motivo' => 'ACCIDENTE AUTOMOVILISTA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 119, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '2087', 'serie' => 'WW574522', 'inicio' => '2026-09-01', 'fin' => '2026-09-03', 'dias' => 3, 'motivo' => 'BURSITIS DE RODILLA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 120, 'curp' => 'HEAI681004MMNRDR06', 'nombre' => 'HERRERA ADAME MARIA IRENE', 'oficio' => '2101', 'serie' => 'VW383769', 'inicio' => '2026-09-02', 'fin' => '2026-09-02', 'dias' => 1, 'motivo' => 'LESIÓN EN BRAZO IZQUIERDO POSIBLE FRACTURA', 'observacion' => 'INICIAL'],
            ['row' => 121, 'curp' => 'HEAI681004MMNRDR06', 'nombre' => 'HERRERA ADAME MARIA IRENE', 'oficio' => '2102', 'serie' => 'MH126357', 'inicio' => '2026-09-03', 'fin' => '2026-09-05', 'dias' => 3, 'motivo' => 'LESIÓN EN BRAZO IZQUIERDO POSIBLE FRACTURA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 122, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJON ZAMUDIO MARIBEL', 'oficio' => '2105', 'serie' => 'WW574550', 'inicio' => '2026-09-04', 'fin' => '2026-09-08', 'dias' => 5, 'motivo' => 'BURSITIS DE RODILLA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 123, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '2123', 'serie' => 'GP636194', 'inicio' => '2026-09-07', 'fin' => '2026-09-20', 'dias' => 14, 'motivo' => null, 'observacion' => 'SUBSECUENTE'],
            ['row' => 124, 'curp' => 'ROHG950710HMNDRS09', 'nombre' => 'RODRÍGUEZ HERRERA JOSÉ GUSTAVO', 'oficio' => '2126', 'serie' => 'UP643597', 'inicio' => '2026-09-06', 'fin' => '2026-10-03', 'dias' => 28, 'motivo' => 'FRACTURA DE CLAVÍCULA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 125, 'curp' => 'CAZM871007MMNSMR05', 'nombre' => 'CASTREJÓN ZAMUDIO MARIBEL', 'oficio' => '2136', 'serie' => 'WW574619', 'inicio' => '2026-09-09', 'fin' => '2026-09-11', 'dias' => 3, 'motivo' => 'BURSITIS DE RODILLA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 126, 'curp' => 'ZAMA890320MMNVRN08', 'nombre' => 'ZAVALA MORALES ANEL GEORGINA', 'oficio' => '2168', 'serie' => 'PC084499', 'inicio' => '2026-09-15', 'fin' => '2026-09-16', 'dias' => 2, 'motivo' => 'SINTOMAS DE DENGUE', 'observacion' => 'INICIAL'],
            ['row' => 127, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'GARCÍA PÉREZ JUAN', 'oficio' => '2170', 'serie' => 'QB079935', 'inicio' => '2026-09-09', 'fin' => '2026-10-06', 'dias' => 28, 'motivo' => 'LUMBALGA CON CIÁTICA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 128, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '2797', 'serie' => 'SH811381', 'inicio' => '2026-09-16', 'fin' => '2026-09-23', 'dias' => 8, 'motivo' => 'HERIDA POR ACCIDENTE EN MOTO', 'observacion' => 'INICIAL'],
            ['row' => 129, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '2214', 'serie' => 'GP636996', 'inicio' => '2026-09-21', 'fin' => '2026-09-27', 'dias' => 7, 'motivo' => 'CÁNCER LINFÁTICO', 'observacion' => 'SUBSECUENTE'],
            ['row' => 130, 'curp' => 'RORA670307HGRDYL08', 'nombre' => 'RODRÍGUEZ RAYO ALEJANDRO', 'oficio' => '2228', 'serie' => 'SG215914', 'inicio' => '2026-09-23', 'fin' => '2026-10-20', 'dias' => 28, 'motivo' => 'LESIÓN DE TENDÓN', 'observacion' => 'SUBSECUENTE'],
            ['row' => 131, 'curp' => 'VIVG921213MMNLLD07', 'nombre' => 'VILLA VILLA MARÍA GUADALUPE', 'oficio' => '2234', 'serie' => 'PC085319', 'inicio' => '2026-09-28', 'fin' => '2026-09-29', 'dias' => 2, 'motivo' => 'PICADURA DE ABEJA', 'observacion' => 'INICIAL'],
            ['row' => 132, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '2244', 'serie' => 'GP637418', 'inicio' => '2026-09-28', 'fin' => '2026-10-11', 'dias' => 14, 'motivo' => 'CÁNCER LINFÁTICO', 'observacion' => 'SUBSECUENTE'],
            ['row' => 133, 'curp' => 'RURG820917MMNBVB07', 'nombre' => 'RUBALCAVA RIVERA GABRIELA', 'oficio' => '2245', 'serie' => 'SG216034', 'inicio' => '2026-09-28', 'fin' => '2026-10-11', 'dias' => 14, 'motivo' => 'ENFERMEDAD GENERAL', 'observacion' => 'SUBSECUENTE'],
            ['row' => 134, 'curp' => 'VIVG921213MMNLLD07', 'nombre' => 'VILLA VILLA MARÍA GUADALUPE', 'oficio' => '2246', 'serie' => 'PC085491', 'inicio' => '2026-09-30', 'fin' => '2026-09-30', 'dias' => 1, 'motivo' => 'ENFERMEDAD GENERAL', 'observacion' => 'SUBSECUENTE'],
            ['row' => 135, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '2247', 'serie' => 'SH811427', 'inicio' => '2026-09-24', 'fin' => '2026-09-24', 'dias' => 1, 'motivo' => 'ENFERMEDAD GENERAL', 'observacion' => 'SUBSECUENTE'],
            ['row' => 136, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUÁREZ JORGE', 'oficio' => 'XXX', 'serie' => 'GP36700', 'inicio' => '2026-09-15', 'fin' => '2026-09-23', 'dias' => 9, 'motivo' => 'ACCIDENTE AUTOMOVILISTA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 137, 'curp' => 'LOSJ830831HDFPRR07', 'nombre' => 'LÓPEZ SUÁREZ JORGE', 'oficio' => 'XXX', 'serie' => 'UP644220', 'inicio' => '2026-09-24', 'fin' => '2026-10-21', 'dias' => 28, 'motivo' => 'ACCIDENTE AUTOMOVILISTA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 138, 'curp' => 'ROHG950710HMNDRS09', 'nombre' => 'RODRIGUEZ HERRERA JOSE GUSTAVO', 'oficio' => '2282', 'serie' => 'UP644547', 'inicio' => '2026-10-04', 'fin' => '2026-10-18', 'dias' => 15, 'motivo' => 'FRACTURA DE CLAVÍCULA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 139, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'GARDUÑO GARCÍA CORINA LIZBETH', 'oficio' => '2309', 'serie' => 'UH198778', 'inicio' => '2026-10-06', 'fin' => '2026-10-16', 'dias' => 11, 'motivo' => 'HERIDA POR ACCIDENTE EN MOTO', 'observacion' => 'INICIAL'],
            ['row' => 140, 'curp' => 'MADF920816HMNRZR01', 'nombre' => 'MARTÍNEZ DÍAZ FRANCISCO JAVIER', 'oficio' => '2310', 'serie' => 'OY081504', 'inicio' => '2026-10-05', 'fin' => '2026-10-09', 'dias' => 5, 'motivo' => 'EXTRACCION DE MUELA', 'observacion' => 'INICIAL'],
            ['row' => 141, 'curp' => 'AARM730623HOCLJG00', 'nombre' => 'ALFARO ROJAS MIGUEL', 'oficio' => '2316', 'serie' => 'UU949791', 'inicio' => '2026-10-07', 'fin' => '2026-10-13', 'dias' => 7, 'motivo' => 'ARTROSIS DE LA RODILLA', 'observacion' => 'INICIAL'],
            ['row' => 142, 'curp' => 'AEOA750326HDFRRR09', 'nombre' => 'ARMENDARIZ ORTEGA ARMANDO', 'oficio' => '2348', 'serie' => 'SD102117', 'inicio' => '2026-10-08', 'fin' => '2026-10-21', 'dias' => 14, 'motivo' => 'ENFERMEDAD GENERAL', 'observacion' => 'INICIAL'],
            ['row' => 143, 'curp' => 'LUSL920715HDFGLS04', 'nombre' => 'LUGARDO SALAZAR LUIS ENRIQUE', 'oficio' => '2359', 'serie' => 'GP638103', 'inicio' => '2026-10-12', 'fin' => '2026-10-13', 'dias' => 2, 'motivo' => 'CÁNCER LINFÁTICO', 'observacion' => 'SUBSECUENTE'],
            ['row' => 144, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'PÉREZ GARCÍA JUAN', 'oficio' => '2376', 'serie' => 'QB081609', 'inicio' => '2026-10-07', 'fin' => '2026-10-18', 'dias' => 12, 'motivo' => 'HERNIA DISCAL', 'observacion' => 'SUBSECUENTE'],
            ['row' => 145, 'curp' => 'QB081609', 'nombre' => 'MARTÍNEZ DURÁN HILDA PATRICIA', 'oficio' => '2377', 'serie' => 'PA213337', 'inicio' => '2026-10-09', 'fin' => '2026-10-22', 'dias' => 14, 'motivo' => 'OPERACIÓN QUISTE', 'observacion' => 'INICIAL'],
            ['row' => 146, 'curp' => 'SAPJ801024HMCNLN07', 'nombre' => 'SANDOVAL PALMA JUAN CARLOS', 'oficio' => '2390', 'serie' => 'SG503951', 'inicio' => '2026-10-13', 'fin' => '2026-11-09', 'dias' => 28, 'motivo' => 'LUXACIÓN', 'observacion' => 'INICIAL'],
            ['row' => 147, 'curp' => 'AARM730623HOCLJG00', 'nombre' => 'ALFARO ROJAS MIGUEL', 'oficio' => '2403', 'serie' => 'SF105445', 'inicio' => '2026-10-14', 'fin' => '2026-10-16', 'dias' => 3, 'motivo' => 'TROMBOSIS VENOSA PROFUNDA', 'observacion' => 'INICIAL'],
            ['row' => 148, 'curp' => 'GAGC891216MMNRRR05', 'nombre' => 'CORINA LIZBETH GARDUÑO GARCIA', 'oficio' => '2406', 'serie' => 'SH811595', 'inicio' => '2026-10-17', 'fin' => '2026-10-25', 'dias' => 9, 'motivo' => 'INSUFICIENCIA VENOSA CRÓNICA', 'observacion' => 'INICIAL'],
            ['row' => 149, 'curp' => 'LALG980318MMNRMR01', 'nombre' => 'GRECIA FERNANDA LARA LEMUS', 'oficio' => '2407', 'serie' => 'OY082747', 'inicio' => '2026-10-19', 'fin' => '2026-10-19', 'dias' => 1, 'motivo' => 'INFECCION ESTOMACAL', 'observacion' => 'INICIAL'],
            ['row' => 150, 'curp' => 'ROMV011205MMDNNA3', 'nombre' => 'RODRÍGUEZ MONDRAGÓN VANESSA EVELYN', 'oficio' => '2411', 'serie' => 'MH127976', 'inicio' => '2026-10-18', 'fin' => '2027-01-09', 'dias' => 84, 'motivo' => 'PREPARTO', 'observacion' => 'INICIAL'],
            ['row' => 151, 'curp' => 'AARM730623HOCLJG00', 'nombre' => 'ALFARO ROJAS MIGUEL', 'oficio' => '2423', 'serie' => 'UU950668', 'inicio' => '2026-10-17', 'fin' => '2026-10-22', 'dias' => 6, 'motivo' => 'ARTROSIS DE LA RODILLA', 'observacion' => 'SUBSECUENTE'],
            ['row' => 152, 'curp' => 'RORA670307HGRDYL08', 'nombre' => 'RODRIGUEZ RAYO ALEJANDRO', 'oficio' => '2433', 'serie' => 'SG216571', 'inicio' => '2026-10-21', 'fin' => '2026-11-03', 'dias' => 14, 'motivo' => 'LESIÓN DE TENDÓN', 'observacion' => 'SUBSECUENTE'],
            ['row' => 153, 'curp' => 'PEGJ790131HPLRRN03', 'nombre' => 'GARCÍA PÉREZ JUAN', 'oficio' => '2457', 'serie' => 'QB082254', 'inicio' => '2026-10-19', 'fin' => '2026-11-15', 'dias' => 28, 'motivo' => 'HERNIA DISCAL', 'observacion' => 'SUBSECUENTE'],
            ['row' => 154, 'curp' => 'AARM730623HOCLJG00', 'nombre' => 'ALFARO ROJAS MIGUEL', 'oficio' => '2459', 'serie' => 'UU951094', 'inicio' => '2026-10-23', 'fin' => '2026-10-29', 'dias' => 7, 'motivo' => 'ARTROSIS DE LA RODILLA', 'observacion' => 'SUBSECUENTE'],
        ];
    }
}
