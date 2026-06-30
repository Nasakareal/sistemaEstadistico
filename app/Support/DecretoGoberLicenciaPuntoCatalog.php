<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class DecretoGoberLicenciaPuntoCatalog
{
    public const SOURCE_FILENAME = 'Decreto Gober.docx';

    public static function affectedArticles(): array
    {
        return [
            '401', '402', '419', '420', '421', '422', '425', '427', '428',
            '429', '430', '431', '432', '433', '435', '436', '437', '438',
            '439', '440', '441', '442', '443', '444', '459', '460', '461',
            '462', '465', '466', '470', '472', '473', '477', '478', '488',
            '489', '492', '493', '494', '499', '503', '508', '521', '593',
            '597', '603', '610', '617', '620', '622', '638', '642', '645',
            '646', '647',
        ];
    }

    public static function rows(): array
    {
        $rows = [];
        $add = function (
            string $articulo,
            ?string $fraccion,
            ?string $inciso,
            string $slug,
            string $nombre,
            int $puntos,
            array $sanciones = [],
            ?string $ambito = null,
            bool $retencion = false,
            bool $depositoCondicionado = false,
            ?int $umaMin = null,
            ?int $umaMax = null,
            ?string $nota = null
        ) use (&$rows): void {
            $rows[] = self::row(
                $articulo,
                $fraccion,
                $inciso,
                $slug,
                $nombre,
                $puntos,
                $sanciones,
                $ambito,
                $retencion,
                $depositoCondicionado,
                $umaMin,
                $umaMax,
                $nota
            );
        };

        $add('401', null, null, 'LICENCIA_CANCELADA_CONDUCIR_ANTES_TRES_ANOS', 'Conducir durante el periodo de cancelacion de licencia', 0, ['amonestacion']);
        $add('402', null, null, 'LICENCIA_FORANEA_SIN_REGISTRO', 'Omitir registro de licencia foranea o extranjera', 0, ['amonestacion']);
        $add('402', null, null, 'LICENCIA_FORANEA_REINCIDENCIA', 'Reincidir en omitir registro de licencia foranea o extranjera', 0, ['arresto']);

        foreach ([
            ['I', 'a', 'CONTROL_DIRECCION', 'Permitir que otro pasajero tome el control de direccion', 1, ['amonestacion'], null, false],
            ['I', 'b', 'CINTURON_SEGURIDAD', 'No asegurar uso correcto del cinturon de seguridad', 1, ['amonestacion'], null, false],
            ['I', 'c', 'PUERTAS_FLUJO', 'Circular con portezuelas abiertas o abrirlas interfiriendo el flujo', 3, ['arresto'], null, false],
            ['I', 'd', 'LUCES_VISIBILIDAD', 'No encender luces cuando disminuye la visibilidad', 1, ['amonestacion'], null, false],
            ['I', 'e', 'DISPOSITIVOS_ADVERTENCIA', 'No colocar dispositivos de advertencia por detencion fortuita', 1, ['amonestacion'], null, false],
            ['II', 'a', 'MOTO_LUCES_ENCENDIDAS', 'Motocicleta sin luces traseras y delanteras encendidas', 1, ['amonestacion'], 'motocicleta', false],
            ['II', 'b', 'MOTO_EXCESO_PERSONAS', 'Motocicleta con mas personas que la tarjeta de circulacion', 3, ['arresto'], 'motocicleta', true],
            ['II', 'c', 'MOTO_REFLEJANTES_NOCTURNOS', 'Motocicleta sin aditamentos luminosos o bandas reflejantes nocturnas', 1, ['amonestacion'], 'motocicleta', false],
            ['II', 'd', 'MOTO_CASCO_PROTECTOR', 'Motocicleta sin casco protector conforme a especificaciones', 3, ['arresto'], 'motocicleta', true],
            ['III', null, 'CARGA_ASEGURADA_TENSORES', 'Transporte de carga sin carga debidamente asegurada', 3, ['suspension'], 'carga', false],
            ['IV', null, 'SUSTANCIAS_CARGA_PROTEGIDA', 'Transporte de sustancias toxicas o peligrosas sin carga protegida y senalizada', 3, ['suspension'], 'sustancias_peligrosas', false],
        ] as $r) {
            $add('419', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7]);
        }

        $art420 = [
            ['II', 'a', 'OBJETOS_OBSTRUYEN_VISIBILIDAD', 'Objetos que obstruyen o distraen la visibilidad', 1, ['amonestacion'], 'automovil', false],
            ['II', 'b', 'OBJETOS_GRAN_TAMANO_PORTEZUELA', 'Objetos de gran tamano entre portezuela y costado izquierdo', 1, ['amonestacion'], 'automovil', false],
            ['II', 'c', 'PERSONAS_ANIMALES_BRAZOS_PIERNAS', 'Sostener personas o animales entre brazos y piernas', 1, ['amonestacion'], 'automovil', false],
            ['II', 'd', 'OBJETOS_DISTRACTOR', 'Utilizar objetos distractores para la conduccion segura', 3, ['arresto'], 'automovil', false],
            ['II', 'e', 'TELEFONIA_MOVIL', 'Usar telefonia movil o medios de comunicacion sin manos libres', 3, ['arresto'], 'automovil', false],
            ['II', 'f', 'AUDIFONOS_AMBOS_OIDOS', 'Usar audifonos en ambos oidos', 3, ['arresto'], 'automovil', false],
            ['II', 'g', 'CLAXON_INNECESARIO', 'Usar claxon de manera innecesaria', 1, ['amonestacion'], 'automovil', false],
            ['II', 'h', 'ACELERAR_INNECESARIAMENTE', 'Acelerar innecesariamente o derrapar para agredir o apresurar', 3, ['arresto'], 'automovil', false],
            ['II', 'i', 'ESTEREO_DECIBELES', 'Aparatos estereofonicos por encima de decibeles permitidos', 3, ['arresto'], 'automovil', false],
            ['II', 'j', 'SENTIDO_CONTRARIO_RAYAS', 'Circular en sentido contrario o invadir contraflujo', 3, ['arresto'], 'automovil', false],
            ['II', 'k', 'CAMBIO_CARRIL_PASOS_DESNIVEL', 'Cambiar de carril en pasos a desnivel o raya continua', 3, ['arresto'], 'automovil', false],
            ['II', 'l', 'ZIGZAGUEAR', 'Circular zigzagueando', 3, ['arresto'], 'automovil', false],
            ['II', 'm', 'EMPAREJARSE_MISMO_CARRIL', 'Emparejarse o rebasar usando un mismo carril', 3, ['arresto'], 'automovil', false],
            ['II', 'n', 'CRUZAR_CENTRO_ESTACIONARSE', 'Cruzar por el centro para estacionarse o circular en sentido contrario', 1, ['amonestacion'], 'automovil', false],
            ['II', 'o', 'SIN_AUXILIARES_PRESCRITOS', 'Conducir sin anteojos u aparatos auxiliares prescritos', 3, ['arresto'], 'automovil', false],
            ['II', 'p', 'EXCESO_PASAJEROS_TARJETA', 'Transportar mas personas que las senaladas en tarjeta de circulacion', 1, ['amonestacion'], 'automovil', false],
            ['II', 'q', 'PERSONAS_EXTERIOR_CARROCERIA', 'Transportar personas en exterior de carroceria fuera de excepciones', 3, ['arresto'], 'automovil', false],
            ['II', 'r', 'PANTALLAS_PARTE_DELANTERA', 'Instalar pantallas o video en la parte delantera', 3, ['arresto'], 'automovil', false],
            ['II', 's', 'LUCES_NIEBLA_SIN_CONDICIONES', 'Usar luces auxiliares de niebla sin condiciones adversas', 3, ['arresto'], 'automovil', false],
            ['III', 'a', 'MOTO_CARGA_IMPIDE_CONTROL', 'Motocicleta con carga que impide control', 2, ['amonestacion'], 'motocicleta', false],
            ['III', 'b', 'MOTO_SUJETARSE_VEHICULO', 'Motocicleta sujeta a otro vehiculo en movimiento', 2, ['amonestacion'], 'motocicleta', false],
            ['III', 'c', 'MOTO_PASAJERO_ENTRE_MANUBRIO', 'Motocicleta con pasajero entre conductor y manubrio', 6, ['suspension'], 'motocicleta', true],
            ['III', 'd', 'MOTO_MENOR_DOCE', 'Motocicleta transporta menor de doce anos', 6, ['suspension'], 'motocicleta', true],
            ['IV', 'a', 'TRANSPORTE_CARGAR_COMBUSTIBLE_PASAJEROS', 'Transporte publico, escolar o personal cargando combustible con pasajeros', 6, ['suspension'], 'transporte_publico', false],
            ['IV', 'b', 'TRANSPORTE_VIDRIOS_POLARIZADOS', 'Transporte publico, escolar o personal con vidrios polarizados no permitidos', 6, ['suspension'], 'transporte_publico', false],
            ['V', 'a', 'CARGA_PASAJEROS_AREA', 'Vehiculo de carga con pasajeros en area de carga', 3, ['arresto'], 'carga', false],
            ['V', 'b', 'CARGA_EXCESIVA_OBSTRUYE', 'Vehiculo de carga con exceso u obstruccion sin permiso', 3, ['arresto'], 'carga', false],
            ['VI', 'a', 'SUSTANCIAS_PERSONAS_AJENAS', 'Transporte de sustancias toxicas o peligrosas con personas ajenas', 3, ['arresto'], 'sustancias_peligrosas', false],
            ['VI', 'b', 'SUSTANCIAS_DESCARGA_VENTEO', 'Arrojar, descargar o ventear sustancias toxicas o peligrosas', 6, ['cancelacion'], 'sustancias_peligrosas', false],
        ];
        foreach ($art420 as $r) {
            $add('420', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7]);
        }

        foreach ([
            ['I', 'a', 'PARTICULAR_MENOR_SIN_PERMISO', 'Menor de edad sin permiso vigente o conduciendo motocicleta', 'automovil'],
            ['I', 'b', 'PARTICULAR_SIN_LICENCIA_VIGENTE', 'Mayor de edad sin licencia vigente correspondiente', 'automovil'],
            ['IV', 'a', 'CARGA_SIN_LICENCIA_VIGENTE', 'Operador de carga sin licencia vigente correspondiente', 'carga'],
            ['IV', 'b', 'CARGA_SIN_CONCESION', 'Operador de carga sin concesion correspondiente', 'carga'],
            ['V', 'a', 'SUSTANCIAS_SIN_LICENCIA_VIGENTE', 'Operador de sustancias peligrosas sin licencia vigente correspondiente', 'sustancias_peligrosas'],
        ] as $r) {
            $add('421', $r[0], $r[1], $r[2], $r[3], 3, ['amonestacion'], $r[4]);
        }

        foreach ([
            ['I', 'a', 'PLACA_LUGAR_DESTINADO', 'Placas no colocadas en lugar destinado', ['amonestacion'], false],
            ['I', 'b', 'PLACA_OBSTRUIDA', 'Placas con objeto o sustancia que obstruye visibilidad', ['amonestacion'], false],
            ['I', 'c', 'PLACAS_NO_COINCIDEN', 'Placas o datos no coinciden con calcomania, tarjeta o REV', ['arresto'], true],
            ['I', 'd', 'PLACA_DIMENSION_NOM', 'Placas sin dimension o caracteristicas NOM', ['amonestacion'], false],
            ['I', 'e', 'MOTO_PLACA_NO_VISIBLE', 'Motocicleta con placa en lugar no visible o inclinacion indebida', ['amonestacion'], false],
            ['II', null, 'SIN_CALCOMANIA_PERMANENTE', 'No contar con calcomania de circulacion permanente', ['amonestacion'], false],
            ['III', null, 'SIN_TARJETA_CIRCULACION', 'No contar con tarjeta de circulacion vigente', ['amonestacion'], false],
            [null, null, 'SIN_CONSTANCIA_REGISTRO_TEMPORAL', 'No portar constancia de registro temporal cuando corresponda', ['amonestacion'], false],
        ] as $r) {
            $add('422', $r[0], $r[1], $r[2], $r[3], 4, $r[4], null, $r[5]);
        }

        $add('425', null, null, 'USO_INDEBIDO_PLACAS_TARJETA', 'Usar tarjeta, placas, calcomanias u hologramas en vehiculo diverso', 3, ['arresto'], null, true);

        foreach ([
            ['I', 'CIRCULAR_CARRIL_DERECHA', 'Transporte publico no circula por carril de extrema derecha', 3, ['amonestacion']],
            ['II', 'NO_COMPARTIR_CON_CICLISTAS', 'Transporte publico no comparte carril con ciclistas con separacion lateral', 6, ['suspension']],
            ['III', 'NO_CARRIL_EXCLUSIVO', 'Transporte publico no usa carril exclusivo cuando existe', 3, ['amonestacion']],
            ['IV', 'PUERTAS_ABIERTAS_ASCENSO', 'Transporte publico circula con portezuelas abiertas o ascenso inseguro', 3, ['amonestacion']],
            ['V', 'SIN_TIEMPO_ASCENSO_DESCENSO', 'Transporte publico no otorga tiempo suficiente de ascenso o descenso', 6, ['suspension']],
            ['VI', 'ASCENSO_DESCENSO_NO_AUTORIZADO', 'Transporte publico realiza ascenso o descenso fuera de lugar autorizado', 6, ['suspension']],
            ['VII', 'LUCES_INTERIORES_NOCTURNAS', 'Transporte publico sin luces interiores encendidas en horario nocturno', 1, ['amonestacion']],
            ['VIII', 'BASE_ESTACIONAMIENTO_NO_AUTORIZADO', 'Transporte publico hace base o se estaciona fuera de lugar u horario autorizado', 3, ['amonestacion']],
        ] as $r) {
            $add('427', $r[0], null, $r[1], $r[2], $r[3], $r[4], 'transporte_publico');
        }

        foreach ([
            ['I', 'COLECTIVO_CARRILES_CENTRALES', 'Transporte colectivo circula por carriles centrales sin autorizacion', 3, ['amonestacion']],
            ['II', 'COLECTIVO_REBASA_CONTRAFLUJO', 'Transporte colectivo rebasa en carril de contraflujo fuera de excepcion', 3, ['amonestacion']],
            ['III', 'COLECTIVO_ASCENSO_SEGUNDO_TERCER_CARRIL', 'Transporte colectivo realiza ascenso o descenso en segundo o tercer carril', 3, ['amonestacion']],
            ['IV', 'COLECTIVO_AUDIO_EXCESIVO', 'Transporte colectivo usa audio danino o molesto', 2, ['amonestacion']],
            ['V', 'COLECTIVO_COMBUSTIBLE_USUARIOS', 'Transporte colectivo carga combustible con usuarios a bordo', 6, ['suspension']],
        ] as $r) {
            $add('428', $r[0], null, $r[1], $r[2], $r[3], $r[4], 'transporte_publico');
        }

        foreach ([
            ['I', 'AGRESION_AGENTES', 'Insultar, denigrar o golpear agentes o promotores', 5, ['arresto']],
            ['II', 'MALTRATO_USUARIO_VIA', 'Maltratar, intimidar o maniobrar contra otra persona usuaria de la via', 5, ['arresto']],
            ['III', 'CLAXON_DISTINTO_SINIESTRO', 'Usar claxon con fin distinto a evitar un siniestro', 2, ['amonestacion']],
            ['IV', 'REBASAR_IZQUIERDA_PROHIBIDO', 'Rebasar por la izquierda en supuestos prohibidos', 3, ['amonestacion']],
            ['V', 'REBASAR_SUPUESTOS_PROHIBIDOS', 'Rebasar vehiculos en supuestos prohibidos', 3, ['amonestacion']],
        ] as $r) {
            $add('429', $r[0], null, $r[1], $r[2], $r[3], $r[4]);
        }

        foreach ([
            ['II', 'SACAR_BRAZO_OBJETOS', 'Sacar brazo, parte del cuerpo u objetos'],
            ['III', 'ABRIR_PUERTAS_INMOVILIZACION', 'Abrir puertas antes de inmovilizacion completa o sin cerciorarse'],
            ['IV', 'DESCENDER_MOVIMIENTO', 'Descender del vehiculo en movimiento'],
            ['V', 'OBSTACULIZAR_AGENTES', 'Obstaculizar labores de agentes o inspectores'],
            ['VI', 'ARROJAR_BASURA_OBJETOS', 'Arrojar basura u objetos en via publica'],
        ] as $r) {
            $add('430', $r[0], null, 'CONDUCTOR_' . $r[1], 'Conductor: ' . $r[2], 0, ['amonestacion']);
            $add('430', $r[0], null, 'OPERADOR_' . $r[1], 'Operador: ' . $r[2], 0, ['amonestacion', 'arresto'], 'transporte_publico');
        }

        foreach ([
            ['I', 'INTERSECCION_SEMAFORO_PEATON', 'No respetar preferencia peatonal en interseccion con semaforo'],
            ['II', 'INTERSECCION_SIN_SEMAFORO', 'No ceder paso en interseccion sin semaforo'],
            ['III', 'PRIORIDAD_ARROYO_PEATONAL', 'No respetar prioridad de uso del arroyo por peatones o no motorizados'],
            ['IV', 'CRUCE_ACERA_PREDIO', 'No respetar preferencia al cruzar acera para entrar o salir de predio'],
            ['V', 'ZONA_ESCOLAR_PREFERENCIA', 'No respetar prioridad y precauciones en zona escolar'],
            ['VI', 'CALLE_PEATONAL_PRIORIDAD', 'No respetar prioridad en calles peatonales'],
        ] as $r) {
            $add('431', $r[0], null, 'CONDUCTOR_' . $r[1], 'Conductor: ' . $r[2], 3);
            $add('431', $r[0], null, 'OPERADOR_' . $r[1], 'Operador transporte publico: ' . $r[2], 3, ['arresto'], 'transporte_publico');
        }

        foreach ([
            ['I', 'PORTAR_LICENCIA', 'No portar licencia correspondiente'],
            ['II', 'ACATAR_INDICACIONES', 'No acatar indicaciones de agentes, inspectores o promotores'],
            ['III', 'RESPETAR_SENALIZACION', 'No respetar senalizacion vial'],
            ['IV', 'PRECAUCION_USUARIOS_VULNERABLES', 'No tomar precauciones ante peatones, ciclistas o no motorizados'],
            ['V', 'COMPARTIR_CARRILES', 'No compartir carriles o cambiar de forma escalonada'],
            ['VI', 'SENTIDO_VIA', 'No circular en el sentido indicado de la via'],
            ['VII', 'REBASAR_IZQUIERDA_SEPARACION', 'No rebasar por izquierda o sin separacion lateral'],
            ['VIII', 'ALINEARSE_DERECHA', 'No alinearse a la derecha al ser rebasado'],
            ['IX', 'DISTANCIA_RAZONABLE', 'No conservar distancia razonable con vehiculo precedente'],
            ['X', 'DIRECCIONALES', 'No indicar giro o cambio de carril con direccionales'],
            ['XI', 'PERMITIR_INCORPORACION', 'No reducir velocidad para permitir incorporacion senalada'],
            ['XII', 'CEDER_SERVICIO_SOCIAL', 'No ceder paso a vehiculos de servicio social en emergencia'],
            ['XIII', 'PRECAUCION_TRANSPORTE_ESCOLAR', 'No tomar precauciones ante transporte escolar'],
            ['XIV', 'ZONAS_ESCOLARES', 'No cumplir reglas de transito en zonas escolares'],
            ['XV', 'VIAS_FERREAS', 'No cumplir reglas al cruzar vias ferreas'],
            ['XVI', 'ASCENSO_CARRIL_ACERA', 'No realizar ascenso o descenso sobre carril contiguo a la acera'],
            ['XVII', 'PORTAR_REGLAMENTO', 'No portar ejemplar fisico o digital del Reglamento'],
            ['XVIII', 'ESPACIO_REBASE', 'No dejar espacio para rebase de tercer vehiculo'],
            ['XIX', 'CARAVANA_ESPACIO', 'No guardar espacio suficiente al circular en caravana'],
            ['XX', 'CARRIL_EXTREMA_DERECHA', 'No circular por carril de extrema derecha fuera de excepciones'],
            ['XXI', 'OTRAS_DISPOSICIONES', 'Incumplir otras disposiciones juridicas aplicables'],
        ] as $r) {
            $add('432', $r[0], null, $r[1], $r[2], 3, ['arresto']);
        }

        foreach ([
            ['I', 'CONDUCTOR_CARRETERA_80', 'Conductor particular excede limite de 80 km/h en carretera estatal', 3, ['arresto'], 'automovil'],
            ['I', 'OPERADOR_CARRETERA_80', 'Operador de transporte publico excede limite de 80 km/h en carretera estatal', 4, ['suspension'], 'transporte_publico'],
            ['II', 'CONDUCTOR_PERIFERICO_MORELIA', 'Conductor particular excede limites del periferico de Morelia', 3, ['arresto'], 'automovil'],
            ['II', 'CARGA_PERIFERICO_MORELIA', 'Operador de carga incumple limites o carriles del periferico de Morelia', 4, ['suspension'], 'carga'],
            ['III', 'CONDUCTOR_VIA_PRIMARIA_60', 'Conductor excede limite de 60 km/h en via primaria', 3, ['amonestacion'], 'automovil'],
            ['III', 'OPERADOR_VIA_PRIMARIA_60', 'Operador de transporte publico excede limite de 60 km/h en via primaria', 4, ['arresto'], 'transporte_publico'],
            ['IV', 'CONDUCTOR_VIA_SECUNDARIA_30', 'Conductor excede limite de 30 km/h en via secundaria', 3, ['arresto'], 'automovil'],
            ['IV', 'OPERADOR_VIA_SECUNDARIA_30', 'Operador de transporte publico excede limite de 30 km/h en via secundaria', 4, ['suspension'], 'transporte_publico'],
            ['V', 'CONDUCTOR_ZONA_ESCOLAR_20', 'Conductor excede limite de 20 km/h en zona escolar u hospitalaria', 4, ['suspension'], 'automovil'],
            ['V', 'OPERADOR_ZONA_ESCOLAR_20', 'Operador de transporte publico excede limite de 20 km/h en zona escolar u hospitalaria', 5, ['cancelacion'], 'transporte_publico'],
            ['VI', 'CONDUCTOR_ESTACIONAMIENTO_10', 'Conductor u operador excede limite de 10 km/h en estacionamiento o via peatonal', 3, ['amonestacion'], 'automovil'],
            ['VI', 'OPERADOR_ESTACIONAMIENTO_10', 'Operador de transporte publico excede limite de 10 km/h en estacionamiento o via peatonal', 6, ['cancelacion'], 'transporte_publico'],
        ] as $r) {
            $add('433', $r[0], null, $r[1], $r[2], $r[3], $r[4], $r[5]);
        }

        foreach ([
            ['I', 'PREFERENCIA_PEATONES', 'No respetar preferencia de paso de personas peatonas', 3, ['amonestacion']],
            ['II', 'PREFERENCIA_CICLISTAS_NO_MOTORIZADOS', 'No respetar preferencia de ciclistas o no motorizados', 3, ['amonestacion']],
            ['III', 'PREFERENCIA_SERVICIO_SOCIAL', 'No respetar preferencia de vehiculos de servicio social', 2, ['amonestacion']],
            ['IV', 'PREFERENCIA_FERROCARRIL_TRANSPORTE', 'No respetar preferencia de ferrocarril o transporte confinado', 2, ['amonestacion']],
            ['V', 'INDICACIONES_AGENTE_INTERSECCION', 'No seguir indicaciones de agente o promotor en interseccion', 5, ['suspension']],
            ['VI', 'REGLAS_SEMAFORO', 'No respetar reglas de semaforo en interseccion', 2, ['amonestacion']],
            ['VII', 'ACCESO_CONTROLADO', 'No ceder paso en incorporacion o desincorporacion de acceso controlado', 3, ['amonestacion']],
            ['VIII', 'INTERSECCION_SIN_SENALAMIENTO', 'No respetar jerarquia de paso en interseccion sin semaforo', 3, ['amonestacion']],
            ['IX', 'GLORIETA', 'No respetar reglas de circulacion en glorieta', 3, ['amonestacion']],
            ['X', 'VUELTA_CONTINUA', 'Dar vuelta continua donde esta prohibida o sin ceder paso', 3, ['amonestacion']],
            ['XI', 'REDUCCION_CARRILES', 'No respetar preferencia o intercalado en reduccion de carriles', 3, ['amonestacion']],
            ['XII', 'PENDIENTE_PREFERENCIA_ASCENDENTE', 'No respetar preferencia de vehiculo ascendente en pendiente', 3, ['amonestacion']],
        ] as $r) {
            $add('435', $r[0], null, $r[1], $r[2], $r[3], $r[4]);
        }

        foreach ([
            ['I', null, 'CONDUCTOR_CRUCE_PEATONAL', 'Conductor detenido sobre cruce peatonal o interseccion', 2, ['amonestacion'], 'automovil', false],
            ['I', null, 'OPERADOR_CRUCE_PEATONAL', 'Operador detenido sobre cruce peatonal o interseccion', 2, ['amonestacion'], 'transporte_publico', false],
            ['II', null, 'CONDUCTOR_AREA_ESPERA', 'Conductor detenido sobre area de espera ciclista o motociclista', 1, ['amonestacion'], 'automovil', false],
            ['II', null, 'OPERADOR_AREA_ESPERA', 'Operador detenido sobre area de espera ciclista o motociclista', 2, ['amonestacion'], 'transporte_publico', false],
            ['III', 'a', 'CONDUCTOR_ACERAS', 'Conductor circula o se detiene sobre aceras o vias peatonales', 3, ['arresto'], 'automovil', true],
            ['III', 'b', 'CONDUCTOR_VIAS_CICLISTAS', 'Conductor circula o se detiene sobre vias ciclistas', 3, ['arresto'], 'automovil', true],
            ['III', 'a', 'OPERADOR_ACERAS', 'Operador circula o se detiene sobre aceras o vias peatonales', 3, ['arresto'], 'transporte_publico', true],
            ['III', 'b', 'OPERADOR_VIAS_CICLISTAS', 'Operador circula o se detiene sobre vias ciclistas', 3, ['arresto'], 'transporte_publico', true],
            ['IV', null, 'CONDUCTOR_SENALAMIENTO_RESTRICTIVO', 'Conductor se detiene con senalamiento restrictivo o guarnicion amarilla', 3, ['amonestacion'], 'automovil', true],
            ['IV', null, 'OPERADOR_SENALAMIENTO_RESTRICTIVO', 'Operador se detiene con senalamiento restrictivo o guarnicion amarilla', 3, ['amonestacion'], 'transporte_publico', true],
            ['V', null, 'OBSTACULIZAR_COLUMNAS', 'Obstaculizar columnas militares, escolares, desfiles o cortejos', 1, ['amonestacion'], null, false],
            ['VI', null, 'CONDUCTOR_REBASAR_CEDEN_PEATONES', 'Conductor rebasa vehiculos detenidos para ceder paso peatonal', 3, ['amonestacion'], 'automovil', false],
            ['VI', null, 'OPERADOR_REBASAR_CEDEN_PEATONES', 'Operador rebasa vehiculos detenidos para ceder paso peatonal', 3, ['amonestacion'], 'transporte_publico', false],
            ['VII', null, 'MOVIMIENTO_CONTRARIO_SENALIZACION', 'Movimiento contrario a senalizacion en carriles de giro', 2, ['amonestacion'], null, false],
            ['VIII', null, 'CONDUCTOR_VUELTA_U', 'Conductor da vuelta en U cerca de curva o donde esta prohibido', 3, ['amonestacion'], 'automovil', false],
            ['VIII', null, 'OPERADOR_VUELTA_U', 'Operador da vuelta en U cerca de curva o donde esta prohibido', 3, ['amonestacion'], 'transporte_publico', false],
            ['IX', null, 'CIRCULAR_ACOTAMIENTO', 'Circular sobre acotamiento fuera de excepciones', 2, ['amonestacion'], null, false],
            ['X', 'a', 'CARRIL_CONFINADO_CIRCULAR', 'Circular en carriles confinados de transporte publico', 6, ['suspension'], null, false],
            ['X', 'b', 'CONDUCTOR_CARRIL_CONFINADO_ASCENSO', 'Conductor realiza ascenso, descenso, carga o descarga en carril confinado', 3, ['arresto'], 'automovil', false],
            ['X', 'b', 'OPERADOR_CARRIL_CONFINADO_ASCENSO', 'Operador realiza ascenso, descenso, carga o descarga en carril confinado', 4, ['arresto'], 'transporte_publico', false],
            ['X', 'c', 'CARRIL_CONFINADO_ESTACIONARSE', 'Estacionarse o reparar vehiculo en carril confinado', 1, ['amonestacion'], null, false],
            ['X', 'd', 'OBSTACULIZAR_CARRIL_CONFINADO', 'Obstaculizar carril confinado al dar vuelta o cambiar de cuerpo de circulacion', 4, ['suspension'], null, false],
            ['XI', null, 'CONDUCTOR_ASCENSO_CARRILES_CENTRALES', 'Conductor realiza ascenso o descenso en carriles centrales', 3, ['arresto'], 'automovil', false],
            ['XI', null, 'OPERADOR_ASCENSO_CARRILES_CENTRALES', 'Operador realiza ascenso o descenso en carriles centrales', 4, ['suspension'], 'transporte_publico', false],
            ['XIII', null, 'CONDUCTOR_REVERSA', 'Conductor circula en reversa mas de treinta metros', 3, ['arresto'], 'automovil', false],
            ['XIII', null, 'OPERADOR_REVERSA', 'Operador circula en reversa mas de treinta metros', 3, ['arresto'], 'transporte_publico', false],
            ['XIV', null, 'CONDUCTOR_VEHICULO_EMERGENCIA', 'Conductor circula detras de vehiculo de emergencia', 3, ['amonestacion'], 'automovil', false],
            ['XIV', null, 'OPERADOR_VEHICULO_EMERGENCIA', 'Operador circula detras de vehiculo de emergencia', 3, ['amonestacion'], 'transporte_publico', false],
            ['XV', null, 'CONDUCTOR_OBSTRUIR_EMERGENCIAS', 'Conductor se detiene a distancia que entorpece emergencias', 3, ['amonestacion'], 'automovil', false],
            ['XV', null, 'OPERADOR_OBSTRUIR_EMERGENCIAS', 'Operador se detiene a distancia que entorpece emergencias', 3, ['amonestacion'], 'transporte_publico', false],
            ['XVI', null, 'CARGAR_COMBUSTIBLE_MOTOR', 'Cargar combustible con motor en marcha', 2, ['amonestacion'], null, false],
            ['XVII', null, 'EMPUJAR_REMOLCAR_SIN_GRUA', 'Empujar o remolcar vehiculos motorizados sin grua fuera de excepciones', 2, ['amonestacion'], null, false],
        ] as $r) {
            $add('436', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7]);
        }
        foreach ([
            ['a', 'PEATONES_INTERSECCION', 'Rebasar por carril contrario con peatones u otros vehiculos cruzando'],
            ['b', 'REBASE_MISMO_SENTIDO', 'Rebasar por carril contrario cuando es posible rebasar en el mismo sentido'],
            ['c', 'SIN_VISIBILIDAD_LIBRE', 'Rebasar por carril contrario sin visibilidad o longitud libre suficiente'],
            ['d', 'CIMA_PENDIENTE_CURVA', 'Rebasar por carril contrario cerca de cima, pendiente o curva'],
            ['e', 'INTERSECCION_VIA_FERREA', 'Rebasar por carril contrario a treinta metros o menos de interseccion o via ferrea'],
            ['f', 'FILAS_VEHICULOS', 'Rebasar filas de vehiculos por carril contrario'],
            ['g', 'RAYA_CENTRAL_CONTINUA', 'Rebasar por carril contrario con raya central continua'],
            ['h', 'PRECEDE_INICIO_REBASE', 'Rebasar cuando el vehiculo precedente inicio maniobra de rebase'],
        ] as $r) {
            $add('436', 'XII', $r[0], 'REBASAR_SENTIDO_CONTRARIO_' . $r[1], $r[2], 4, ['suspension', 'cancelacion']);
        }

        $add('437', null, null, 'SERVICIO_SOCIAL_USO_INDEBIDO_SENALES', 'Vehiculo de servicio social usa luces o senales sin atender emergencia o permisos', 3, ['arresto']);

        foreach ([
            ['I', null, 'ESCOLAR_CARRIL_BAJA_ASCENSO', 'Transporte escolar o personal no circula o se detiene en condiciones seguras', 4, ['arresto'], null, null],
            ['II', null, 'ESCOLAR_PUERTAS_CERRADAS', 'Transporte escolar o personal circula con portezuelas abiertas o ascenso inseguro', 3, ['amonestacion'], null, null],
            ['III', 'a', 'ESCOLAR_ASCENSO_DERECHA_AUTORIZADO', 'Ascenso o descenso fuera de carril derecho o lugar autorizado', 6, [], 50, 70],
            ['III', 'b', 'ESCOLAR_ASCENSO_VEHICULO_DETENIDO', 'Permitir ascenso o descenso sin vehiculo totalmente detenido', 6, [], 50, 70],
            ['III', 'c', 'ESCOLAR_LUCES_INTERMITENTES', 'No activar luces intermitentes en ascenso o descenso', 6, ['suspension'], null, null],
            ['IV', null, 'ESCOLAR_LUCES_INTERIORES', 'Transporte escolar o personal sin luces interiores nocturnas', 1, ['amonestacion'], null, null],
            ['V', null, 'ESCOLAR_AUXILIAR_CRUCE', 'No asistir cruce de escolares con auxiliar', 6, ['amonestacion'], null, null],
            ['VI', null, 'ESCOLAR_COMBUSTIBLE_USUARIOS', 'Cargar combustible con usuarios a bordo', 6, ['cancelacion'], null, null],
        ] as $r) {
            $add('438', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], 'transporte_publico', false, false, $r[6], $r[7]);
        }

        foreach ([
            ['I', 'MOTO_CENTRO_CARRIL_DERECHA', 'Motocicleta no circula al centro del carril de extrema derecha'],
            ['II', 'MOTO_REBASE_IZQUIERDA', 'Motocicleta rebasa por lado distinto al izquierdo'],
            ['III', 'MOTO_PREFERENCIA_PASO', 'Motocicleta no respeta reglas de preferencia de paso'],
        ] as $r) {
            $add('439', $r[0], null, $r[1], $r[2], 3, ['amonestacion'], 'motocicleta');
        }

        foreach ([
            ['I', 'MOTO_ACERAS_PEATONES', 'Motocicleta circula sobre aceras o areas peatonales', 4, ['suspension'], false],
            ['II', 'MOTO_VIA_CICLISTA', 'Motocicleta circula por vias exclusivas para ciclistas', 3, ['arresto'], true],
            ['III', 'MOTO_CARRIL_TRANSPORTE', 'Motocicleta circula por carriles confinados de transporte publico', 3, ['amonestacion'], false],
            ['IV', 'MOTO_PUENTE_PEATONAL', 'Motocicleta circula sobre puentes peatonales', 4, ['suspension'], false],
            ['V', 'MOTO_ENTRE_CARRILES', 'Motocicleta circula entre carriles fuera de excepciones', 1, ['amonestacion'], false],
            ['VI', 'MOTO_CARRILES_CENTRALES', 'Motocicleta circula por carriles centrales de acceso controlado', 3, ['arresto'], false],
            ['VII', 'MOTO_VIAS_RESTRINGIDAS', 'Motocicleta circula en vias primarias o restringidas', 3, ['amonestacion'], false],
            ['VIII', 'MOTO_MENORES_DOCE', 'Motocicleta lleva pasajeros menores de doce anos', 6, ['cancelacion'], false],
            ['IX', 'MOTO_MANIOBRAS_RIESGOSAS', 'Motocicleta realiza maniobras riesgosas o temerarias', 3, ['arresto'], false],
        ] as $r) {
            $add('440', $r[0], null, $r[1], $r[2], $r[3], $r[4], 'motocicleta', $r[5]);
        }

        foreach ([
            ['441', 'I', null, 'CARGA_RUTAS_HORARIOS', 'Transporte de carga fuera de vias u horarios establecidos', 4, ['suspension'], 'carga', false, null, null],
            ['441', 'II', null, 'CARGA_CARRIL_DERECHA', 'Transporte de carga no circula por carril derecho', 3, ['amonestacion'], 'carga', false, null, null],
            ['441', 'III', null, 'CARGA_MANIOBRAS_INSEGURAS', 'Transporte de carga realiza maniobras de carga o descarga inseguras', 4, ['arresto'], 'carga', false, null, null],
            ['442', 'I', null, 'CARGA_CARRILES_CENTRALES', 'Transporte de carga circula por carriles centrales prohibidos', 3, ['arresto'], 'carga', false, null, null],
            ['442', 'II', null, 'CARGA_BASE_NO_AUTORIZADA', 'Transporte de carga hace base o estaciona fuera de lugar autorizado', 3, [], 'carga', false, 100, 120],
        ] as $r) {
            $add($r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $r[8], false, $r[9], $r[10]);
        }

        foreach ([
            ['443', 'I', 'SUSTANCIAS_RUTAS_HORARIOS', 'Sustancias peligrosas fuera de rutas, horarios o itinerarios autorizados'],
            ['443', 'II', 'SUSTANCIAS_NO_SOLICITA_PRIORIDAD', 'Sustancias peligrosas no solicita prioridad en congestionamiento'],
            ['443', 'III', 'SUSTANCIAS_LINEAMIENTOS', 'Sustancias peligrosas incumple lineamientos aplicables'],
            ['444', 'I', 'SUSTANCIAS_ESTACIONAR_RIESGO', 'Sustancias peligrosas estaciona en via publica o fuente de riesgo'],
            ['444', 'II', 'SUSTANCIAS_PARADAS_NO_SENALADAS', 'Sustancias peligrosas realiza paradas no senaladas'],
            ['444', 'III', 'SUSTANCIAS_CARGA_DESCARGA_NO_DESTINADA', 'Sustancias peligrosas carga o descarga en lugar no destinado'],
        ] as $r) {
            $add($r[0], $r[1], null, $r[2], $r[3], 6, ['cancelacion'], 'sustancias_peligrosas');
        }

        foreach ([
            ['a', 'CONDICIONES_MECANICAS', 'Vehiculo sin condiciones mecanicas, tecnicas o de seguridad'],
            ['b', 'COMBUSTIBLE_LUBRICANTE', 'Vehiculo sin combustible o lubricante suficiente'],
            ['c', 'SENALIZACION_EMERGENCIA', 'Vehiculo sin equipo de senalizacion de emergencia o abanderamiento'],
            ['d', 'ESPEJOS_RETROVISORES', 'Vehiculo sin espejos retrovisores completos o limpios'],
            ['e', 'FAROS_PRINCIPALES', 'Vehiculo sin faros principales delanteros adecuados'],
            ['f', 'LUCES_POSTERIORES', 'Vehiculo sin luces posteriores rojas visibles'],
            ['g', 'CONEXION_LUCES_PLACA', 'Luces posteriores y luz de placa sin conexion simultanea'],
            ['h', 'REFLECTANTES_ROJOS', 'Vehiculo sin reflectantes rojos posteriores'],
            ['i', 'LUCES_FRENADO', 'Vehiculo sin luces indicadoras de frenado visibles'],
            ['j', 'LUCES_DIRECCIONALES', 'Vehiculo sin luces direccionales adecuadas'],
            ['k', 'LAMPARAS_ESTACIONAMIENTO', 'Vehiculo sin lamparas delanteras de estacionamiento'],
            ['l', 'CUARTOS_FRONTALES_TRASEROS', 'Vehiculo sin cuartos frontales o traseros visibles'],
            ['m', 'LUZ_PLACA', 'Vehiculo sin mecanismo que ilumine placa posterior'],
            ['n', 'LUCES_REVERSA', 'Vehiculo sin luces de reversa'],
            ['o', 'CINTURONES_SEGURIDAD', 'Vehiculo sin cinturones de seguridad adecuados'],
            ['p', 'BOCINA', 'Vehiculo sin bocina audible'],
            ['q', 'SILENCIADOR_ESCAPE', 'Vehiculo sin silenciador de escape adecuado'],
            ['r', 'VELOCIMETRO', 'Vehiculo sin velocimetro iluminado y funcional'],
            ['s', 'PARABRISAS_CRISTALES', 'Vehiculo con parabrisas o cristales deficientes'],
            ['t', 'LIMPIAPARABRISAS', 'Vehiculo sin limpiadores de parabrisas funcionales'],
            ['u', 'LLANTAS', 'Vehiculo con llantas inseguras o sin refaccion adecuada'],
            ['v', 'HERRAMIENTA_LLANTAS', 'Vehiculo sin herramienta para cambio de llantas'],
            ['w', 'DISPOSITIVOS_EXTINTOR', 'Vehiculo sin dispositivos de emergencia o extintor'],
        ] as $r) {
            $add('459', 'II', $r[0], 'MOTORIZADO_' . $r[1], $r[2], 1, ['amonestacion']);
        }
        $add('459', 'II', 'x', 'RETENCION_INFANTIL', 'Transportar menores sin sistema de retencion infantil correspondiente', 6, ['suspension']);
        $add('459', 'III', null, 'TRANSPORTE_PUBLICO_EQUIPAMIENTO', 'Transporte publico o de personal sin equipamiento obligatorio', 0, [], 'transporte_publico', false, false, 50, 60);
        $add('459', 'IV', null, 'TRANSPORTE_ESCOLAR_EQUIPAMIENTO', 'Transporte escolar sin equipamiento obligatorio', 0, [], 'transporte_publico', false, false, 50, 60);
        $add('459', 'V', null, 'TRANSPORTE_CARGA_EQUIPAMIENTO', 'Transporte de carga sin equipamiento obligatorio', 0, [], 'carga', false, false, 50, 60);

        foreach ([
            ['I', 'LUCES_AMBAR_SERVICIOS_NOCTURNOS', 'Vehiculo particular de servicios nocturnos sin luces ambar autorizadas'],
            ['II', 'LUCES_AMBAR_TRANSPORTE_LARGO', 'Transporte publico mayor a diez metros sin luces ambar autorizadas'],
            ['III', 'LUCES_AMBAR_DOBLE_REMOLQUE', 'Transporte de carga doble remolque sin luces ambar autorizadas'],
            ['IV', 'LUCES_AMBAR_GRUAS', 'Grua sin luces ambar o faros buscadores autorizados'],
            ['V', 'LUCES_AMBAR_DIMENSIONES_MAQUINARIA', 'Vehiculo con dimensiones excesivas o maquinaria sin luces ambar autorizadas'],
        ] as $r) {
            $add('460', $r[0], null, $r[1], $r[2], 3, ['amonestacion']);
        }
        foreach ([
            ['I', 'ACOPLAMIENTO_REBASA_DEFENSA', 'Dispositivo de acoplamiento rebasa la defensa'],
            ['II', 'REMOLQUE_BANDAS_LAMPARAS', 'Remolque sin bandas reflejantes o lamparas de frenado'],
            ['III', 'REMOLQUE_LUCES_FRENO', 'Luces de freno no visibles en parte posterior del remolque'],
        ] as $r) {
            $add('461', $r[0], null, $r[1], $r[2], 3, ['amonestacion']);
        }
        $add('462', null, null, 'SIN_SALPICADERAS_PESO_TRES_TONELADAS', 'Vehiculo de tres mil kilogramos o mas sin salpicaderas posteriores', 6, ['suspension']);

        foreach ([
            ['I', 'SONIDO_EXCESIVO', 'Dispositivos o sistemas de sonido con volumen excesivo', 3, ['amonestacion'], false],
            ['II', 'SIRENAS_TORRETAS', 'Sirenas, torretas, estrobos o codigos reservados', 6, ['suspension'], true],
            ['III', 'NEUMATICOS_METALICOS', 'Bandas de oruga, ruedas o neumaticos metalicos que danan la via', 6, ['suspension'], false],
            ['IV', 'FAROS_DESLUMBRANTES', 'Faros deslumbrantes fuera de NOM o riesgosos', 6, ['suspension'], false],
            ['V', 'PLACAS_OBSTRUIDAS_NEON', 'Luces de neon, portaplacas o micas que obstruyen placas', 4, ['arresto'], false],
            ['VI', 'ANTIRADARES', 'Sistemas antiradares o detectores de radares', 6, ['suspension'], true],
            ['VII', 'ESCAPE_RUIDO', 'Modificar escape para provocar ruido excesivo', 3, ['amonestacion'], false],
            ['VIII', 'ANUNCIOS_PUBLICITARIOS', 'Anuncios publicitarios no autorizados', 3, ['amonestacion'], false],
            ['IX', 'CLAXON_RUIDO_EXCESIVO', 'Bocina o claxon con ruido excesivo o sonido diverso', 3, ['amonestacion'], false],
            ['X', 'CROMATICA_PROHIBIDA', 'Vehiculo particular con cromatica reservada o similar', 6, ['suspension'], true],
            ['XI', 'POLARIZADO_MAYOR_20', 'Polarizado u oscurecimiento mayor al veinte por ciento', 3, ['amonestacion'], true],
        ] as $r) {
            $add('465', $r[0], null, $r[1], $r[2], $r[3], $r[4], null, $r[5]);
        }

        $add('466', 'I', null, 'SIN_TARJETA_CIRCULACION_VIGENTE', 'Vehiculo sin tarjeta de circulacion vigente', 4, ['arresto']);
        $add('466', 'II', null, 'NO_ENTREGAR_TARJETA_AGENTES', 'No conservar o entregar tarjeta de circulacion a agentes', 4, ['arresto']);
        $add('470', null, null, 'NO_NOTIFICAR_CAMBIO_MOTOR_CARROCERIA', 'No notificar cambio de motor o modificacion de carroceria', 3, ['amonestacion']);
        $add('472', null, null, 'SIN_PLACAS_PERMISO_PROVISIONAL', 'Circular sin placas o permiso provisional vigente', 2, ['amonestacion']);
        $add('473', null, null, 'ALTERAR_PLACAS_DECORATIVAS', 'Remachar, soldar, modificar o sustituir placas con objetos decorativos', 2, ['amonestacion']);
        foreach ([
            ['I', 'PLACAS_DEMOSTRACION_HORARIO', 'Usar placas de demostracion fuera del horario autorizado', false],
            ['II', 'PLACAS_DEMOSTRACION_RADIO', 'Usar placas de demostracion fuera del radio autorizado', false],
            ['III', 'PLACAS_DEMOSTRACION_VEHICULO', 'Usar placas de demostracion en vehiculo no autorizado', true],
        ] as $r) {
            $add('477', $r[0], null, $r[1], $r[2], 3, ['amonestacion'], null, $r[3]);
        }
        $add('478', null, null, 'PLACAS_TRASLADO_VENCIDAS', 'Usar placas de traslado vencidas', 3, ['arresto']);
        $add('488', null, null, 'DISCAPACIDAD_USO_INDEBIDO', 'Uso indebido de espacio, placas o beneficio para discapacidad', 3, ['suspension']);
        $add('489', null, null, 'TARJETON_USO_INDEBIDO', 'Uso indebido de tarjeton para personas con discapacidad', 6, ['suspension', 'cancelacion']);
        $add('492', null, null, 'PERMISO_PROVISIONAL_VENCIDO', 'Conducir con permiso provisional vencido', 2, ['amonestacion']);
        $add('493', null, null, 'PERMISO_PROVISIONAL_LUGAR_DIVERSO', 'Portar permiso provisional en lugar diverso al autorizado', 2, ['amonestacion']);
        $add('494', null, null, 'SIN_CALCOMANIA_PLACAS', 'Circular sin calcomania correspondiente a placas vigentes', 1, ['amonestacion']);
        $add('499', 'I', null, 'NO_INSCRIBIR_VEHICULO_NUEVO', 'No inscribir vehiculo nuevo en el REV dentro de quince dias', 3, ['amonestacion']);
        $add('499', 'III', null, 'NO_INSCRIBIR_CAMBIO_PROPIETARIO', 'No inscribir cambio de propietario en el REV dentro de treinta dias', 3, ['amonestacion']);
        $add('503', null, null, 'REGISTRO_VISITA_VENCIDO', 'Circular con registro de visita vencido', 2, ['arresto'], null, true);

        foreach ([
            ['I', 'ALCOHOL_DROGAS_CONDUCTOR', 'Conducir vehiculo con alcoholemia superior al limite o bajo sustancias', 'automovil'],
            ['II', 'ALCOHOL_MOTOCICLETA', 'Conducir motocicleta con alcoholemia superior al limite', 'motocicleta'],
        ] as $r) {
            $add('508', $r[0], null, $r[1], $r[2], 3, ['arresto'], $r[3], false, true);
        }
        $add('508', 'III', null, 'INGERIR_BEBIDAS_VEHICULO', 'Ingerir bebidas embriagantes o sustancias en vehiculo automotor', 1, ['arresto']);
        $add('508', 'IV', null, 'ALCOHOL_DROGAS_OPERADOR', 'Operador de transporte publico o especializado con alcohol o sustancias', 6, ['suspension'], 'transporte_publico', true);
        $add('521', null, null, 'EMBESTIR_USUARIO_VULNERABLE_SIN_LESIONES', 'Embestir a peaton, ciclista o no motorizado sin ocasionar lesiones', 3, ['arresto'], 'siniestro');

        foreach ([
            ['I', 'ADEMAN_ALTO', 'Desatender ademan de alto realizado por agente'],
            ['II', 'ADEMAN_SIGA', 'Desatender ademan de siga realizado por agente'],
            ['III', 'ADEMAN_PREVENTIVA', 'Desatender ademan de preventiva realizado por agente'],
            ['IV', 'ADEMAN_ALTO_GENERAL', 'Desatender ademan de alto general realizado por agente'],
        ] as $r) {
            $add('593', $r[0], null, $r[1], $r[2], 3, ['amonestacion']);
        }

        foreach ([
            ['I', 'SEMAFORO_VERDE', 'Incumplir indicacion verde de semaforo'],
            ['II', 'FLECHA_VERDE', 'Incumplir indicacion de flecha verde'],
            ['III', 'AMBAR', 'Incumplir indicacion ambar o ambar intermitente'],
            ['IV', 'ROJO', 'Incumplir luz roja de semaforo'],
            ['VI', 'ROJO_FLECHA_VERDE', 'Realizar movimiento distinto a flecha verde con rojo'],
            ['VII', 'FLECHA_ROJA', 'Avanzar contra flecha roja'],
            ['IX', 'ROJO_DESTELLANTE', 'Incumplir rojo destellante'],
            ['X', 'FERROCARRIL_SEMAFOROS_BARRERAS', 'Incumplir semaforos, campanas o barreras de ferrocarril'],
        ] as $r) {
            $add('597', $r[0], null, $r[1], $r[2], 2, ['amonestacion']);
        }
        $add('603', null, null, 'ALTERAR_SENALES_SIN_PERMISO', 'Instalar, retirar, sustituir, alterar, trasladar, ocultar o modificar senales sin permiso', 2, ['amonestacion']);
        $add('610', null, null, 'DESACATAR_SENALES_RESTRICTIVAS', 'Incumplir senales restrictivas', 1, ['amonestacion']);
        $add('617', null, null, 'DESACATAR_SENALES_ESPECIALES', 'Incumplir senales especiales', 1, ['amonestacion']);
        $add('617', null, null, 'DESACATAR_SENALES_ZONA_ESCOLAR', 'Incumplir senales especiales de zonas escolares', 1, ['arresto']);
        $add('620', null, null, 'DESACATAR_SENALES_OBRA', 'Incumplir senales para proteccion de obras', 1, ['amonestacion']);

        foreach ([
            ['I', 'a', 'RAYAS_LONGITUDINALES', 'Rebasar rayas longitudinales', 1, ['amonestacion'], false],
            ['I', 'b', 'RAYA_CONTINUA', 'Rebasar rayas longitudinales continuas', 1, ['amonestacion'], false],
            ['I', 'd', 'RAYA_DOBLE_CONTINUA', 'Rebasar con raya longitudinal doble continua de su lado', 2, ['amonestacion'], false],
            ['I', 'e', 'RAYAS_TRANSVERSALES', 'Invadir rayas transversales', 2, ['amonestacion'], false],
            ['I', 'g', 'RAYAS_ESTACIONAMIENTO', 'Invadir o sobrepasar rayas de estacionamiento', 1, ['amonestacion'], false],
            ['II', 'a', 'GUARNICION_BLANCA', 'Estacionarse sin causa en guarnicion blanca', 3, ['amonestacion'], true],
            ['II', 'b', 'GUARNICION_AMARILLA', 'Estacionarse sin causa en guarnicion amarilla', 1, ['amonestacion'], true],
            ['II', 'c', 'GUARNICION_ROJA', 'Estacionarse sin causa en guarnicion roja', 1, ['amonestacion'], true],
            ['VII', null, 'ISLETAS', 'Circular o estacionarse en isletas', 1, ['amonestacion'], true],
        ] as $r) {
            $add('622', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], null, $r[6]);
        }

        $add('638', null, null, 'MAQUINARIA_AGRICOLA_CONSTRUCCION', 'Maquinaria agricola o de construccion sin autorizacion o medidas de seguridad', 4, ['arresto']);
        foreach ([
            ['I', 'REPARACIONES_VIA_PUBLICA', 'Efectuar reparaciones a vehiculos fuera de emergencia', 3, ['amonestacion'], true, null, null],
            ['II', 'COMPETENCIAS_MANIOBRAS_RIESGOSAS', 'Organizar o participar en competencias, acrobacias o maniobras riesgosas', 3, ['amonestacion'], true, null, null],
            ['III', 'OBJETOS_RESIDUOS_CIRCULACION', 'Colocar, arrojar o abandonar objetos o residuos que entorpezcan circulacion', 3, ['amonestacion'], true, null, null],
            ['IV', 'DANAR_SENALIZACION', 'Utilizar inadecuadamente, obstruir, danar o destruir senalizacion vial', 3, ['amonestacion'], true, null, null],
            ['V', 'RESERVAR_ESTACIONAMIENTO', 'Colocar objetos o senalizacion para reservar estacionamiento sin autorizacion', 3, ['amonestacion'], true, null, null],
            ['VI', 'CERRAR_OBSTRUIR_CIRCULACION', 'Cerrar u obstruir circulacion sin autorizacion', 3, ['amonestacion'], true, null, null],
            ['VII', 'DISPOSITIVOS_CONTROL_OBSTACULIZAN', 'Instalar dispositivos para control de transito que obstaculicen o afecten la via', 0, ['arresto'], false, 30, 40],
            ['VIII', 'SIMBOLOS_SENALIZACION_PUBLICIDAD', 'Usar simbolos y leyendas de senalizacion vial con fines publicitarios', 0, [], false, 60, 80],
            ['IX', 'LUCES_DISTRACTORAS', 'Colocar luces o dispositivos que confundan o distraigan', 0, [], false, 30, 40],
            ['X', 'TRABAJOS_SIN_SENALES', 'Efectuar trabajos en via publica sin senalamientos de desvio y proteccion', 0, [], false, 25, 40],
            ['XI', 'NO_RETIRAR_VEHICULO_OBRAS', 'Mantener vehiculo estacionado tras requerimiento de retiro por obras o servicios', 0, [], true, 30, 40],
        ] as $r) {
            $add('642', $r[0], null, $r[1], $r[2], $r[3], $r[4], null, $r[5], false, $r[6], $r[7]);
        }
        $add('645', null, null, 'ESTACIONAMIENTO_INDEBIDO_GENERAL', 'Estacionarse afectando desplazamiento u obstruyendo cochera', 2, ['amonestacion']);
        $add('646', null, null, 'PREFERENCIA_ESTACIONAMIENTO_DISCAPACIDAD_SOCIAL', 'Contravenir preferencia de estacionamiento para discapacidad o servicio social', 3, ['amonestacion']);

        foreach ([
            ['I', null, 'BANQUETAS_CRUCES_VIAS_CICLISTAS', 'Estacionar sobre banquetas, cruces o vias ciclistas', 3, ['amonestacion'], false],
            ['II', null, 'VIAS_PRIMARIAS', 'Estacionar en vias primarias', 1, ['amonestacion'], true],
            ['III', null, 'PUENTES_TUNELES', 'Estacionar sobre o debajo de puentes, estructuras elevadas o tuneles', 4, ['arresto'], true],
            ['IV', null, 'COSTADO_IZQUIERDO_CAMELLON', 'Estacionar en costado izquierdo con camellones, islas o glorietas', 1, ['amonestacion'], true],
            ['V', null, 'SENALAMIENTO_GUARNICION_AMARILLA', 'Estacionar donde exista senalamiento restrictivo o guarnicion amarilla', 1, ['amonestacion'], true],
            ['VI', null, 'CARRILES_TRANSPORTE_PUBLICO', 'Estacionar en carriles preferentes o confinados de transporte publico', 6, ['suspension'], true],
            ['VII', null, 'AREAS_TRANSPORTE_PUBLICO', 'Estacionar en areas de estaciones, terminales, sitios o ascenso de transporte publico', 6, ['suspension'], true],
            ['VIII', null, 'ESPACIOS_SERVICIOS_ESPECIALES', 'Estacionar en espacios de servicios especiales autorizados', 4, ['arresto'], true],
            ['IX', null, 'ASCENSO_DESCENSO_TIEMPO_EXCEDIDO', 'Exceder tiempo en espacios especiales de ascenso y descenso', 2, ['amonestacion'], true],
            ['X', 'a', 'FRENTE_BANCOS', 'Estacionar frente a bancos', 4, ['arresto'], true],
            ['X', 'b', 'FRENTE_HIDRANTES', 'Estacionar frente a hidrantes', 4, ['arresto'], true],
            ['X', 'c', 'FRENTE_EMERGENCIA', 'Estacionar frente a entradas o salidas de vehiculos de emergencia', 4, ['arresto'], true],
            ['X', 'd', 'FRENTE_ESTACIONAMIENTOS_GASOLINERAS', 'Estacionar frente a estacionamientos publicos o gasolineras', 4, ['arresto'], true],
            ['X', 'e', 'FRENTE_CENTROS_ESCOLARES', 'Estacionar frente a centros escolares o concentracion masiva', 4, ['arresto'], true],
            ['X', 'f', 'FRENTE_RAMPAS_PEATONALES', 'Estacionar frente a rampas peatonales', 4, ['arresto'], true],
            ['X', 'g', 'FRENTE_RAMPAS_VEHICULOS', 'Estacionar frente a rampas de acceso de vehiculos fuera de excepcion', 4, ['arresto'], true],
            ['X', 'h', 'FRENTE_HOSPITALES', 'Estacionar en entradas o salidas peatonales de hospitales o centros de salud', 4, ['arresto'], true],
            ['XI', null, 'OBSTRUIR_VISIBILIDAD_SENALIZACION', 'Estacionar obstruyendo visibilidad de senalizacion vial', 1, ['amonestacion'], true],
            ['XII', null, 'DOBLE_FILA', 'Estacionar en doble o mas filas', 3, ['amonestacion'], true],
            ['XIII', null, 'BATERIA_NO_PERMITIDA', 'Estacionar en bateria fuera de excepciones', 1, ['amonestacion'], true],
            ['XIV', 'a', 'MENOR_SIETE_METROS_ESQUINA', 'Estacionar a menos de siete metros y medio de esquina', 1, ['amonestacion'], true],
            ['XIV', 'b', 'MENOR_BOMBEROS_EMERGENCIA', 'Estacionar a distancia menor de estacion de bomberos o emergencia', 1, ['amonestacion'], true],
            ['XIV', 'c', 'MENOR_DIEZ_METROS_FERREA', 'Estacionar a menos de diez metros de cruce de via ferrea', 1, ['amonestacion'], true],
            ['XV', null, 'CAJON_DISCAPACIDAD', 'Estacionar en cajon para personas con discapacidad', 6, ['suspension'], true],
            ['XVI', 'a', 'CARRETERA_VEHICULO_OPUESTO', 'Estacionar en carretera a menos de cincuenta metros de vehiculo opuesto', 2, ['amonestacion'], true],
            ['XVI', 'b', 'CARRETERA_CURVA_CIMA', 'Estacionar en carretera a menos de cien metros de curva o cima sin visibilidad', 2, ['amonestacion'], true],
            ['XVII', null, 'SENTIDO_CONTRARIO', 'Estacionar en sentido contrario a la circulacion', 2, ['amonestacion'], true],
            ['XVIII', null, 'CAJONES_EXCLUSIVOS', 'Estacionar en cajones exclusivos autorizados', 6, ['suspension'], true],
            ['XIX', null, 'VIAS_CICLISTAS_CONTIGUO', 'Estacionar en vias ciclistas o espacio contiguo', 4, ['amonestacion'], true],
        ] as $r) {
            $add('647', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], null, $r[6]);
        }

        return $rows;
    }

    public static function assertSourceCoversRows(?string $sourcePath = null): void
    {
        $sourcePath = $sourcePath ?: public_path(self::SOURCE_FILENAME);
        $sourceArticles = array_fill_keys(self::sourceArticleNumbers($sourcePath), true);
        $missing = [];

        foreach (self::rows() as $row) {
            $articulo = (string) ($row['articulo'] ?? '');
            if ($articulo !== '' && !isset($sourceArticles[$articulo])) {
                $missing[] = $articulo;
            }
        }

        $missing = array_values(array_unique($missing));
        if ($missing !== []) {
            throw new RuntimeException(
                'El archivo public/' . self::SOURCE_FILENAME . ' no contiene los articulos esperados: ' . implode(', ', $missing) . '.'
            );
        }
    }

    public static function sourceArticleNumbers(string $sourcePath): array
    {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('No se encontro el archivo fuente: ' . $sourcePath . '.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extension ZipArchive es necesaria para validar ' . self::SOURCE_FILENAME . '.');
        }

        $zip = new ZipArchive();
        if ($zip->open($sourcePath) !== true) {
            throw new RuntimeException('No se pudo abrir el documento fuente: ' . $sourcePath . '.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($xml) || trim($xml) === '') {
            throw new RuntimeException('No se pudo leer word/document.xml dentro de ' . self::SOURCE_FILENAME . '.');
        }

        $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], "\n", $xml));
        preg_match_all('/Art(?:i|í)culo\s+(\d+)\./u', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private static function row(
        string $articulo,
        ?string $fraccion,
        ?string $inciso,
        string $slug,
        string $nombre,
        int $puntos,
        array $sanciones,
        ?string $ambito,
        bool $retencion,
        bool $depositoCondicionado,
        ?int $umaMin,
        ?int $umaMax,
        ?string $nota
    ): array {
        $payload = [
            'codigo' => self::codigo($articulo, $fraccion, $inciso, $slug),
            'nombre' => Str::limit($nombre, 150, ''),
            'articulo' => $articulo,
            'fraccion' => $fraccion,
            'inciso' => $inciso,
            'ambito_vehiculo' => $ambito ?: self::inferirAmbito($articulo, $fraccion, $slug, $nombre),
            'puntos' => $puntos,
            'multa_uma_min' => $umaMin,
            'multa_uma_max' => $umaMax,
            'amonestacion' => in_array('amonestacion', $sanciones, true),
            'arresto_persona' => in_array('arresto', $sanciones, true),
            'suspension_licencia' => in_array('suspension', $sanciones, true),
            'cancelacion_licencia' => in_array('cancelacion', $sanciones, true),
            'deposito_si_sin_persona_habilitada' => $depositoCondicionado,
            'retencion_vehiculo' => $retencion,
            'descripcion' => $nombre,
            'activa' => true,
        ];

        $payload['fundamento_legal'] = self::fundamentoLegal($payload, $nota);

        return $payload;
    }

    private static function codigo(string $articulo, ?string $fraccion, ?string $inciso, string $slug): string
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

    private static function fundamentoLegal(array $payload, ?string $nota): string
    {
        $partes = [self::referenciaLegal($payload['articulo'], $payload['fraccion'], $payload['inciso'])];
        $sanciones = [];

        if ($payload['multa_uma_min'] !== null || $payload['multa_uma_max'] !== null) {
            $sanciones[] = 'multa de ' . self::multaUmaTexto($payload['multa_uma_min'], $payload['multa_uma_max']);
        }
        if ($payload['amonestacion']) {
            $sanciones[] = 'amonestacion';
        }
        if ($payload['arresto_persona']) {
            $sanciones[] = 'arresto hasta por 36 horas';
        }
        if ($payload['suspension_licencia']) {
            $sanciones[] = 'suspension de la licencia o permiso para conducir';
        }
        if ($payload['cancelacion_licencia']) {
            $sanciones[] = 'cancelacion de la licencia o permiso para conducir';
        }
        if ((int) $payload['puntos'] > 0) {
            $puntos = (int) $payload['puntos'];
            $sanciones[] = $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos') . ' de penalizacion a la licencia para conducir';
        }
        if ($payload['retencion_vehiculo']) {
            $sanciones[] = 'remision del vehiculo al deposito cuando proceda conforme al supuesto';
        } elseif ($payload['deposito_si_sin_persona_habilitada']) {
            $sanciones[] = 'remision del vehiculo al deposito si no se encuentra persona apta o habilitada para conducir';
        }

        $partes[] = $payload['descripcion'];
        if ($sanciones !== []) {
            $partes[] = 'Sancion: ' . implode('; ', $sanciones) . '.';
        }
        if ($nota) {
            $partes[] = rtrim($nota, '.') . '.';
        }

        return implode(': ', array_filter($partes));
    }

    private static function referenciaLegal(string $articulo, ?string $fraccion, ?string $inciso): string
    {
        $partes = ['Articulo ' . $articulo];
        if ($fraccion) {
            $partes[] = 'fraccion ' . $fraccion;
        }
        if ($inciso) {
            $partes[] = 'inciso ' . $inciso;
        }

        return implode(', ', $partes);
    }

    private static function inferirAmbito(string $articulo, ?string $fraccion, string $slug, string $nombre): string
    {
        $texto = Str::ascii(strtoupper($articulo . ' ' . ($fraccion ?? '') . ' ' . $slug . ' ' . $nombre));

        if (str_contains($texto, 'MOTO') || $articulo === '440' || $articulo === '439' || ($articulo === '419' && $fraccion === 'II')) {
            return 'motocicleta';
        }
        if (str_contains($texto, 'SUSTANCIA') || str_contains($texto, 'TOXICA') || str_contains($texto, 'PELIGROSA')) {
            return 'sustancias_peligrosas';
        }
        if (str_contains($texto, 'CARGA') || str_contains($texto, 'REMOLQUE')) {
            return 'carga';
        }
        if (str_contains($texto, 'TRANSPORTE PUBLICO') || str_contains($texto, 'OPERADOR') || str_contains($texto, 'ESCOLAR')) {
            return 'transporte_publico';
        }
        if (str_contains($texto, 'SINIESTRO') || str_contains($texto, 'EMBESTIR')) {
            return 'siniestro';
        }

        return 'general';
    }

    private static function multaUmaTexto(?int $min, ?int $max): string
    {
        if ($min !== null && $max !== null) {
            return $min === $max ? $min . ' UMAS' : $min . ' a ' . $max . ' UMAS';
        }

        return $min !== null ? $min . ' UMAS' : 'hasta ' . $max . ' UMAS';
    }
}
