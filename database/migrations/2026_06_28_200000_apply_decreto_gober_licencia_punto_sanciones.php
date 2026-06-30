<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABLE = 'licencia_punto_infracciones';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, 'suspension_licencia')) {
                $table->boolean('suspension_licencia')->default(false)->after('arresto_persona')->index();
            }

            if (!Schema::hasColumn(self::TABLE, 'cancelacion_licencia')) {
                $table->boolean('cancelacion_licencia')->default(false)->after('suspension_licencia')->index();
            }
        });

        $now = now();
        foreach ($this->rows() as $row) {
            $exists = DB::table(self::TABLE)->where('codigo', $row['codigo'])->exists();

            DB::table(self::TABLE)->updateOrInsert(
                ['codigo' => $row['codigo']],
                array_merge($row, [
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['cancelacion_licencia', 'suspension_licencia'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function rows(): array
    {
        return [
            $this->row('419', 'I', 'a,b,d,e', 'SEGURIDAD_ABDE_DECRETO', 'Seguridad en vehiculo motorizado: control, cinturon, luces y advertencias', 1, true, false, false, false, false, 'Las personas que infrinjan los incisos a), b), d) y e) seran sancionadas con amonestacion y un punto de penalizacion en la licencia para conducir.'),
            $this->row('419', 'I', 'c', 'PUERTAS_DECRETO', 'Circular con portezuelas cerradas y abrirlas sin interferir el flujo', 3, false, true, false, false, false, 'En caso de infringir el inciso c), se sanciona con arresto hasta por 36 horas y tres puntos de penalizacion a la licencia para conducir.'),
            $this->row('419', 'II', 'A', 'MOTO_LUCES_DECRETO', 'Motocicleta sin luces delanteras y traseras encendidas', 1, true, false, false, false, false, 'Las personas conductoras de motocicletas que infrinjan el inciso a) seran sancionadas con amonestacion y un punto de penalizacion a la licencia para conducir.'),
            $this->row('419', 'II', 'C', 'MOTO_REFLEJANTES_DECRETO', 'Motocicleta sin aditamentos luminosos o bandas reflejantes en horario nocturno', 1, true, false, false, false, false, 'Las personas conductoras de motocicletas que infrinjan el inciso c) seran sancionadas con amonestacion y un punto de penalizacion a la licencia para conducir.'),
            $this->row('419', 'II', 'B', 'MOTO_EXCESO_PERSONAS_DECRETO', 'Motocicleta con exceso de personas conforme a tarjeta de circulacion', 3, false, true, false, false, true, 'En caso de infringir el inciso b), se sanciona con arresto hasta por 36 horas, tres puntos de penalizacion a la licencia para conducir y remision de la motocicleta al deposito.'),
            $this->row('419', 'II', 'D', 'MOTO_CASCO_PROTECTOR_DECRETO', 'Motocicleta sin casco protector conforme a especificaciones', 3, false, true, false, false, true, 'En caso de infringir el inciso d), se sanciona con arresto hasta por 36 horas, tres puntos de penalizacion a la licencia para conducir y remision de la motocicleta al deposito.'),
            $this->row('419', 'III', null, 'CARGA_ASEGURADA_DECRETO', 'Transporte de carga sin carga debidamente asegurada', 3, false, false, true, false, false, 'Las personas operadoras de vehiculos de transporte de carga seran sancionadas con suspension de la licencia o permiso y tres puntos de penalizacion.'),
            $this->row('419', 'IV', null, 'SUSTANCIAS_SENALIZADA_DECRETO', 'Transporte de sustancias toxicas o peligrosas sin carga protegida y senalizada', 3, false, false, true, false, false, 'Las personas operadoras de vehiculos de transporte de sustancias toxicas o peligrosas seran sancionadas con suspension de la licencia o permiso y tres puntos de penalizacion.'),

            $this->row('420', 'II', 'a,b,g,p', 'MOTORIZADO_1P', 'Obstruir visibilidad, claxon innecesario o exceder pasajeros', 1, true, false, false, false, false, 'Incisos a), b), g) y p): amonestacion y un punto de penalizacion.'),
            $this->row('420', 'II', 'c,n', 'MOTORIZADO_1P_B', 'Sostener personas u objetos o cruzar para estacionarse en sentido contrario', 1, true, false, false, false, false, 'Incisos c) y n): amonestacion y un punto de penalizacion.'),
            $this->row('420', 'II', 'd,e,f,h,i,j,k,l,m,o,q,r,s', 'MOTORIZADO_3P', 'Distractores, celular, sentido contrario, zigzagueo u otras conductas de riesgo', 3, false, true, false, false, false, 'Incisos d), e), f), h), i), j), k), l), m), o), q), r) y s): arresto hasta por 36 horas y tres puntos.'),
            $this->row('420', 'III', 'a,b', 'MOTO_CARGA_SUJETARSE', 'Motocicleta con carga que impide control o sujeta a otro vehiculo', 2, true, false, false, false, false, 'Incisos a) y b): amonestacion y dos puntos de penalizacion.'),
            $this->row('420', 'III', 'c,d', 'MOTO_PASAJERO_MENOR', 'Motocicleta con pasajero entre conductor y manubrio o menor de doce anos', 6, false, false, true, false, true, 'Incisos c) y d): suspension de la licencia o permiso, seis puntos y remision de la motocicleta al deposito.'),
            $this->row('420', 'IV', 'a,b', 'TRANSPORTE_PUBLICO_ESCOLAR', 'Transporte publico, escolar o de personal con combustible o vidrios no permitidos', 6, false, false, true, false, false, 'Fraccion IV: suspension de la licencia o permiso y seis puntos.'),
            $this->row('420', 'V', 'a', 'CARGA_PASAJEROS_AREA', 'Vehiculo de carga con pasajeros en area de carga', 3, false, true, false, false, false, 'Fraccion V inciso a): arresto hasta por 36 horas y tres puntos.'),
            $this->row('420', 'V', 'b', 'CARGA_EXCESIVA', 'Vehiculo de carga con exceso, obstruccion o sobresaliente sin permiso', 3, false, true, false, false, false, 'Fraccion V inciso b): arresto hasta por 36 horas y tres puntos.'),
            $this->row('420', 'VI', 'a', 'SUSTANCIAS_PERSONAS_AJENAS', 'Transporte de sustancias toxicas o peligrosas con personas ajenas', 3, false, true, false, false, false, 'Fraccion VI inciso a): arresto hasta por 36 horas y tres puntos.'),
            $this->row('420', 'VI', 'b', 'SUSTANCIAS_DESCARGA', 'Arrojar, descargar o ventear sustancias toxicas o peligrosas', 6, false, false, false, true, false, 'Fraccion VI inciso b): cancelacion de la licencia o permiso y seis puntos.'),

            $this->row('422', null, null, 'DOCUMENTOS_PLACAS_GENERAL', 'Incumplir obligaciones de placas, calcomania, tarjeta o registro temporal', 4, true, false, false, false, true, 'El incumplimiento del articulo 422 se sanciona con amonestacion y cuatro puntos; en el inciso c) de la fraccion I aplica arresto hasta por 36 horas, cuatro puntos y remision al deposito vehicular.'),
            $this->row('422', 'I', 'c', 'PLACAS_NO_COINCIDEN', 'Placas o datos que no coinciden con calcomania, tarjeta o REV', 4, false, true, false, false, true, 'Articulo 422, fraccion I, inciso c): arresto hasta por 36 horas, cuatro puntos y remision del vehiculo al deposito vehicular.'),
            $this->row('425', null, null, 'USO_INDEBIDO_PLACAS_TARJETA', 'Usar tarjeta, placas, calcomanias u hologramas en vehiculo diverso', 3, false, true, false, false, true, 'Articulo 425: arresto hasta por 36 horas, tres puntos y remision del vehiculo al deposito hasta acreditar legitima propiedad.'),

            $this->row('436', 'III', null, 'CONDUCTOR_AREA_RESTRINGIDA', 'Conductor circula o se detiene en areas restringidas, aceras o vias ciclistas', 3, false, true, false, false, true, 'Articulo 436, fraccion III: arresto hasta por 36 horas, tres puntos y remision del vehiculo al deposito.'),
            $this->row('436', 'III', null, 'OPERADOR_AREA_RESTRINGIDA', 'Operador circula o se detiene en areas restringidas, aceras o vias ciclistas', 3, false, true, false, false, true, 'Articulo 436, fraccion III: arresto hasta por 36 horas, tres puntos y remision del vehiculo al deposito.'),
            $this->row('436', 'IV', null, 'CONDUCTOR_SENALAMIENTO_RESTRICTIVO', 'Conductor se detiene donde existe senalamiento restrictivo o guarnicion amarilla', 3, true, false, false, false, true, 'Articulo 436, fraccion IV: amonestacion, tres puntos y remision del vehiculo al deposito.'),
            $this->row('436', 'IV', null, 'OPERADOR_SENALAMIENTO_RESTRICTIVO', 'Operador se detiene donde existe senalamiento restrictivo o guarnicion amarilla', 3, true, false, false, false, true, 'Articulo 436, fraccion IV: amonestacion, tres puntos y remision del vehiculo al deposito.'),
            $this->row('436', 'X', 'a', 'CARRIL_CONFINADO_CIRCULAR', 'Circular en carriles confinados de transporte publico', 6, false, false, true, false, false, 'Articulo 436, fraccion X, inciso a): suspension de la licencia o permiso y seis puntos.'),
            $this->row('436', 'X', 'b', 'CONDUCTOR_CARRIL_CONFINADO_ASCENSO', 'Conductor realiza ascenso, descenso, carga o descarga en carril confinado', 3, false, true, false, false, false, 'Articulo 436, fraccion X, inciso b): arresto hasta por 36 horas y tres puntos.'),
            $this->row('436', 'X', 'b', 'OPERADOR_CARRIL_CONFINADO_ASCENSO', 'Operador realiza ascenso, descenso, carga o descarga en carril confinado', 4, false, true, false, false, false, 'Articulo 436, fraccion X, inciso b): arresto hasta por 36 horas y cuatro puntos.'),
            $this->row('436', 'X', 'd', 'OBSTACULIZAR_CARRIL_CONFINADO', 'Obstaculizar carriles confinados al dar vuelta o cambiar de cuerpo de circulacion', 4, false, false, true, false, false, 'Articulo 436, fraccion X, inciso d): suspension de la licencia o permiso y cuatro puntos.'),

            $this->row('439', null, null, 'MOTO_REGLAS_CIRCULACION', 'Motocicleta incumple reglas de circulacion de carril, rebase o preferencia', 3, true, false, false, false, false, 'Articulo 439: amonestacion y tres puntos de penalizacion a la licencia para conducir.'),
            $this->row('440', 'I', null, 'MOTO_ACERAS_PEATONES', 'Motocicleta circula sobre aceras o areas peatonales', 4, false, false, true, false, false, 'Articulo 440, fraccion I: suspension de la licencia o permiso y cuatro puntos.'),
            $this->row('440', 'II', null, 'MOTO_VIA_CICLISTA', 'Motocicleta circula por vias exclusivas para ciclistas', 3, false, true, false, false, true, 'Articulo 440, fraccion II: arresto hasta por 36 horas, tres puntos y remision de la motocicleta al deposito.'),
            $this->row('440', 'III', null, 'MOTO_CARRIL_TRANSPORTE', 'Motocicleta circula por carriles confinados de transporte publico', 3, true, false, false, false, false, 'Articulo 440, fraccion III: amonestacion y tres puntos.'),
            $this->row('440', 'IV', null, 'MOTO_PUENTE_PEATONAL', 'Motocicleta circula sobre puentes peatonales', 4, false, false, true, false, false, 'Articulo 440, fraccion IV: suspension de la licencia o permiso y cuatro puntos.'),
            $this->row('440', 'V', null, 'MOTO_ENTRE_CARRILES', 'Motocicleta circula entre carriles fuera de excepciones', 1, true, false, false, false, false, 'Articulo 440, fraccion V: amonestacion y un punto.'),
            $this->row('440', 'VI', null, 'MOTO_CARRILES_CENTRALES', 'Motocicleta circula por carriles centrales de acceso controlado', 3, false, true, false, false, false, 'Articulo 440, fraccion VI: arresto hasta por 36 horas y tres puntos.'),
            $this->row('440', 'VII', null, 'MOTO_VIAS_RESTRINGIDAS', 'Motocicleta circula en vias primarias o restringidas por senalizacion', 3, true, false, false, false, false, 'Articulo 440, fraccion VII: amonestacion y tres puntos.'),
            $this->row('440', 'VIII', null, 'MOTO_MENORES_DOCE', 'Motocicleta lleva pasajeros menores de doce anos', 6, false, false, false, true, false, 'Articulo 440, fraccion VIII: cancelacion de la licencia o permiso y seis puntos.'),
            $this->row('440', 'IX', null, 'MOTO_MANIOBRAS_RIESGOSAS', 'Motocicleta realiza maniobras riesgosas o temerarias', 3, false, true, false, false, false, 'Articulo 440, fraccion IX: arresto hasta por 36 horas y tres puntos.'),

            $this->row('465', 'II', null, 'SIRENAS_TORRETAS', 'Instalar o utilizar sirenas, torretas, estrobos o codigos reservados', 6, false, false, true, false, true, 'Articulo 465, fraccion II: suspension de la licencia o permiso, seis puntos y remision del vehiculo al deposito hasta retirar dispositivos.'),
            $this->row('465', 'V', null, 'PLACAS_OBSTRUIDAS_NEON', 'Luces de neon, portaplacas o micas que obstruyan placas', 4, false, true, false, false, false, 'Articulo 465, fraccion V: arresto hasta por 36 horas y cuatro puntos.'),
            $this->row('465', 'VI', null, 'ANTIRADARES', 'Instalar o utilizar sistemas antiradares o detectores de radares', 6, false, false, true, false, true, 'Articulo 465, fraccion VI: suspension de la licencia o permiso, seis puntos y remision del vehiculo al deposito hasta retirar sistemas.'),
            $this->row('465', 'X', null, 'CROMATICA_PROHIBIDA', 'Vehiculo particular con cromatica similar a transporte publico o servicios oficiales', 6, false, false, true, false, true, 'Articulo 465, fraccion X: suspension de la licencia o permiso, seis puntos y remision del vehiculo al deposito hasta retirar cromatica.'),
            $this->row('465', 'XI', null, 'POLARIZADO_MAYOR_20', 'Peliculas de control solar o polarizado mayor al veinte por ciento', 3, true, false, false, false, true, 'Articulo 465, fraccion XI: amonestacion, tres puntos y remision del vehiculo al deposito hasta retirar pelicula de control solar.'),

            $this->row('477', null, null, 'PLACAS_DEMOSTRACION_USO_INDEBIDO', 'Uso indebido de placas de demostracion', 3, true, false, false, false, true, 'Articulo 477: amonestacion, tres puntos y remision al deposito cuando el uso sea en vehiculo no autorizado.'),
            $this->row('478', null, null, 'PLACAS_TRASLADO_VENCIDAS', 'Uso de placas de traslado vencidas', 3, false, true, false, false, false, 'Articulo 478: arresto hasta por 36 horas y tres puntos de penalizacion.'),
            $this->row('488', null, null, 'DISCAPACIDAD_USO_INDEBIDO', 'Uso indebido de cajon, placas o beneficio para personas con discapacidad', 3, false, false, true, false, false, 'Articulo 488: suspension de la licencia o permiso y tres puntos; puede cancelarse la placa otorgada cuando corresponda.'),
            $this->row('489', null, null, 'TARJETON_USO_INDEBIDO', 'Uso indebido de tarjeton o beneficio de transporte', 6, false, false, true, true, false, 'Articulo 489: suspension o cancelacion de la licencia y seis puntos, ademas de cancelacion del tarjeton correspondiente.'),
        ];
    }

    private function row(
        string $articulo,
        ?string $fraccion,
        ?string $inciso,
        string $slug,
        string $nombre,
        int $puntos,
        bool $amonestacion,
        bool $arresto,
        bool $suspension,
        bool $cancelacion,
        bool $retencion,
        string $fundamento
    ): array {
        return [
            'codigo' => $this->codigo($articulo, $fraccion, $inciso, $slug),
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
            'suspension_licencia' => $suspension,
            'cancelacion_licencia' => $cancelacion,
            'deposito_si_sin_persona_habilitada' => $arresto,
            'retencion_vehiculo' => $retencion,
            'descripcion' => $nombre,
            'fundamento_legal' => $this->referenciaLegal($articulo, $fraccion, $inciso) . ': ' . $fundamento,
            'activa' => true,
        ];
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

        return strlen($codigo) <= 50
            ? $codigo
            : substr($codigo, 0, 43) . '_' . strtoupper(substr(sha1($codigo), 0, 6));
    }

    private function referenciaLegal(string $articulo, ?string $fraccion, ?string $inciso): string
    {
        $partes = ['Articulo ' . $articulo];
        if ($fraccion) {
            $partes[] = 'fraccion ' . $fraccion;
        }
        if ($inciso) {
            $partes[] = (str_contains($inciso, ',') ? 'incisos ' : 'inciso ') . $inciso;
        }

        return implode(', ', $partes);
    }

    private function inferirAmbitoVehiculo(string $articulo, ?string $fraccion, string $slug, string $nombre): string
    {
        $texto = Str::ascii(strtoupper($articulo . ' ' . ($fraccion ?? '') . ' ' . $slug . ' ' . $nombre));

        if (str_contains($texto, 'MOTO') || str_contains($texto, 'MOTOCICLETA') || $articulo === '440' || ($articulo === '420' && $fraccion === 'III') || ($articulo === '419' && $fraccion === 'II')) {
            return 'motocicleta';
        }
        if (str_contains($texto, 'SUSTANCIA') || str_contains($texto, 'TOXICA') || str_contains($texto, 'PELIGROSA')) {
            return 'sustancias_peligrosas';
        }
        if (str_contains($texto, 'CARGA')) {
            return 'carga';
        }
        if (str_contains($texto, 'TRANSPORTE PUBLICO') || str_contains($texto, 'OPERADOR') || str_contains($texto, 'ESCOLAR')) {
            return 'transporte_publico';
        }

        return 'general';
    }
};
