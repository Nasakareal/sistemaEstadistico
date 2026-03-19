<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\OperativoDispositivo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuardianesCaminoController extends Controller
{
    protected function obtenerOperativoUnico()
    {
        $catalogo = OperativoCatalogo::where('slug', 'guardianes-del-camino')->firstOrFail();

        $operativo = Operativo::with([
                'catalogo',
                'unidad',
                'delegacion',
                'destacamento',
                'creador',
            ])
            ->where('operativo_catalogo_id', $catalogo->id)
            ->orderBy('id')
            ->first();

        if (!$operativo) {
            $operativo = new Operativo();
            $operativo->captura_uuid = (string) Str::uuid();
            $operativo->fecha = now()->toDateString();
            $operativo->hora = now()->format('H:i');
            $operativo->operativo_catalogo_id = $catalogo->id;
            $operativo->lugar = 'Sin lugar';
            $operativo->descripcion = null;
            $operativo->observaciones = null;
            $operativo->created_by = Auth::id();
            $operativo->updated_by = Auth::id();
            $operativo->save();

            $operativo->load([
                'catalogo',
                'unidad',
                'delegacion',
                'destacamento',
                'creador',
            ]);
        }

        return $operativo;
    }

    protected function obtenerFechaFiltro(Request $request): string
    {
        return $request->input('fecha', now()->format('Y-m-d'));
    }

    protected function obtenerResumenPorFecha(int $operativoId, string $fecha)
    {
        return OperativoDispositivo::query()
            ->select(
                'operativo_dispositivo_catalogo_id',
                DB::raw('SUM(cantidad) as total_cantidad'),
                DB::raw('SUM(vehiculos_inspeccionados) as total_vehiculos_inspeccionados'),
                DB::raw('SUM(personas_inspeccionadas) as total_personas_inspeccionadas'),
                DB::raw('SUM(vehiculos_impactados) as total_vehiculos_impactados'),
                DB::raw('SUM(personas_impactadas) as total_personas_impactadas'),
                DB::raw('SUM(estado_fuerza_participante) as total_estado_fuerza_participante'),
                DB::raw('SUM(kilometros_recorridos) as total_kilometros_recorridos'),
                DB::raw('SUM(acompanamientos) as total_acompanamientos'),
                DB::raw('SUM(abanderamientos) as total_abanderamientos'),
                DB::raw('SUM(auxilios_viales) as total_auxilios_viales'),
                DB::raw('SUM(prox_empresas) as total_prox_empresas'),
                DB::raw('SUM(prox_tiendas_conveniencia) as total_prox_tiendas_conveniencia'),
                DB::raw('SUM(prox_escuelas) as total_prox_escuelas'),
                DB::raw('SUM(prox_hospitales) as total_prox_hospitales'),
                DB::raw('SUM(antecedentes_personas) as total_antecedentes_personas'),
                DB::raw('SUM(antecedentes_vehiculos) as total_antecedentes_vehiculos'),
                DB::raw('SUM(antecedentes_motos) as total_antecedentes_motos'),
                DB::raw('SUM(antecedentes_camiones) as total_antecedentes_camiones'),
                DB::raw('SUM(puestas_disposicion) as total_puestas_disposicion'),
                DB::raw('SUM(vehiculos_recuperados) as total_vehiculos_recuperados'),
                DB::raw('SUM(armas_aseguradas) as total_armas_aseguradas'),
                DB::raw('SUM(mercancia_recuperada) as total_mercancia_recuperada'),
                DB::raw('SUM(decomiso_drogas) as total_decomiso_drogas'),
                DB::raw('GROUP_CONCAT(DISTINCT NULLIF(TRIM(crps_participantes), "") SEPARATOR " | ") as total_crps_participantes')
            )
            ->where('operativo_id', $operativoId)
            ->whereDate('fecha', $fecha)
            ->groupBy('operativo_dispositivo_catalogo_id')
            ->with('catalogo')
            ->get();
    }

    protected function obtenerTotalesGeneralesPorFecha(int $operativoId, string $fecha)
    {
        return OperativoDispositivo::query()
            ->select(
                DB::raw('SUM(cantidad) as cantidad'),
                DB::raw('SUM(vehiculos_inspeccionados) as vehiculos_inspeccionados'),
                DB::raw('SUM(personas_inspeccionadas) as personas_inspeccionadas'),
                DB::raw('SUM(vehiculos_impactados) as vehiculos_impactados'),
                DB::raw('SUM(personas_impactadas) as personas_impactadas'),
                DB::raw('SUM(antecedentes_personas) as antecedentes_personas'),
                DB::raw('SUM(antecedentes_vehiculos) as antecedentes_vehiculos'),
                DB::raw('SUM(antecedentes_motos) as antecedentes_motos'),
                DB::raw('SUM(antecedentes_camiones) as antecedentes_camiones'),
                DB::raw('SUM(puestas_disposicion) as puestas_disposicion'),
                DB::raw('SUM(vehiculos_recuperados) as vehiculos_recuperados'),
                DB::raw('SUM(armas_aseguradas) as armas_aseguradas'),
                DB::raw('SUM(mercancia_recuperada) as mercancia_recuperada'),
                DB::raw('SUM(decomiso_drogas) as decomiso_drogas'),
                DB::raw('SUM(estado_fuerza_participante) as estado_fuerza_participante'),
                DB::raw('SUM(kilometros_recorridos) as kilometros_recorridos'),
                DB::raw('SUM(acompanamientos) as acompanamientos'),
                DB::raw('SUM(abanderamientos) as abanderamientos'),
                DB::raw('SUM(auxilios_viales) as auxilios_viales'),
                DB::raw('SUM(prox_empresas) as prox_empresas'),
                DB::raw('SUM(prox_tiendas_conveniencia) as prox_tiendas_conveniencia'),
                DB::raw('SUM(prox_escuelas) as prox_escuelas'),
                DB::raw('SUM(prox_hospitales) as prox_hospitales'),
                DB::raw('GROUP_CONCAT(DISTINCT NULLIF(TRIM(crps_participantes), "") SEPARATOR " | ") as crps_participantes')
            )
            ->where('operativo_id', $operativoId)
            ->whereDate('fecha', $fecha)
            ->first();
    }

    protected function normalizarNombre(?string $texto): string
    {
        $texto = mb_strtoupper(trim((string) $texto), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

        return $ascii !== false ? $ascii : $texto;
    }

    protected function resumenKeyByNombre($resumen)
    {
        return $resumen->keyBy(function ($item) {
            return $this->normalizarNombre($item->catalogo->nombre ?? '');
        });
    }

    protected function valorResumen($resumenPorNombre, string $nombre, string $campo, $default = 0)
    {
        $item = $resumenPorNombre->get($this->normalizarNombre($nombre));

        return $item->{$campo} ?? $default;
    }

    protected function textoWhatsAppConsolidado(Operativo $operativo, string $fecha, $resumen, $totalesGenerales): string
    {
        $resumenPorNombre = $this->resumenKeyByNombre($resumen);

        $fechaTexto = Carbon::parse($fecha)->format('d/m/Y');
        $horaTexto = now()->format('H:i');
        $destacamento = mb_strtoupper($operativo->destacamento->nombre ?? 'SIN DESTACAMENTO', 'UTF-8');

        $crpsGenerales = trim((string) ($totalesGenerales->crps_participantes ?? ''));
        $crpsGenerales = $crpsGenerales !== '' ? $crpsGenerales : '00';

        $texto = "GUARDIA CIVIL\n\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n\n";
        $texto .= "UNIDAD DE PROTECCIÓN EN CARRETERAS\n\n";
        $texto .= "DESTACAMENTO {$destacamento}\n\n";
        $texto .= "ASUNTO: CONSOLIDADO DE NOVEDADES DE ACTIVIDADES DIARIAS.\n\n";
        $texto .= "{$fechaTexto}         {$horaTexto} hs.\n\n";
        $texto .= "DESCRIPCIÓN GENERAL:\n";
        $texto .= "OPERATIVO GUARDIANES DEL CAMINO\n";

        if (!empty($operativo->descripcion)) {
            $texto .= mb_strtoupper(trim($operativo->descripcion), 'UTF-8') . "\n";
        }

        $texto .= "\nDISPOSITIVOS:\n\n";

        $texto .= "PSV (PUESTO DE SEGURIDAD Y VIGILANCIA): " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS INSPECCIONADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'total_vehiculos_inspeccionados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS INSPECCIONADAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'total_personas_inspeccionadas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE): " . str_pad((string) $this->valorResumen($resumenPorNombre, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS INSPECCIONADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'total_vehiculos_inspeccionados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS INSPECCIONADAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'total_personas_inspeccionadas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "DISPOSITIVO CASCO: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CASCO', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS IMPACTADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CASCO', 'total_vehiculos_impactados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS IMPACTADAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CASCO', 'total_personas_impactadas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CASCO', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'DISPOSITIVO CASCO', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CASCO', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "DISPOSITIVO CINTURÓN: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CINTURÓN', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS IMPACTADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CINTURÓN', 'total_vehiculos_impactados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS IMPACTADAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CINTURÓN', 'total_personas_impactadas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CINTURÓN', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'DISPOSITIVO CINTURÓN', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CINTURÓN', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "DISPOSITIVO CARRUSEL: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CARRUSEL', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS IMPACTADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CARRUSEL', 'total_vehiculos_impactados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CARRUSEL', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'DISPOSITIVO CARRUSEL', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO CARRUSEL', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "CORDILLERA: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CORDILLERA', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS IMPACTADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CORDILLERA', 'total_vehiculos_impactados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS IMPACTADAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CORDILLERA', 'total_personas_impactadas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CORDILLERA', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'CORDILLERA', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'CORDILLERA', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS IMPACTADOS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'total_vehiculos_impactados', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS IMPACTADAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'total_personas_impactadas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "CABALLEROS DEL CAMINO: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_cantidad', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• ACOMPAÑAMIENTOS (ESCOLTAS, CARAVANAS, EMERGENCIAS, OTROS): " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_acompanamientos', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• ABANDERAMIENTOS (HECHOS DE TRÁNSITO, EVENTOS, OTROS): " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_abanderamientos', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• AUXILIOS VIALES (FALLAS MECÁNICAS, PEATÓN, OTROS): " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_auxilios_viales', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_estado_fuerza_participante', 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: " . ($this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_crps_participantes', '00') ?: '00') . "\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) $this->valorResumen($resumenPorNombre, 'CABALLEROS DEL CAMINO', 'total_kilometros_recorridos', 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "PROXIMIDAD SOCIAL\n";
        $texto .= "- EMPRESAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PROXIMIDAD SOCIAL', 'total_prox_empresas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "- TIENDAS DE CONVENIENCIA: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PROXIMIDAD SOCIAL', 'total_prox_tiendas_conveniencia', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "- ESCUELAS: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PROXIMIDAD SOCIAL', 'total_prox_escuelas', 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "- HOSPITALES: " . str_pad((string) $this->valorResumen($resumenPorNombre, 'PROXIMIDAD SOCIAL', 'total_prox_hospitales', 0), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "TOTALES:\n\n";
        $texto .= "INSPECCIONES DE PERSONAS Y/O VEHÍCULOS:\n";
        $texto .= "VEHÍCULOS INSPECCIONADOS: " . str_pad((string) ($totalesGenerales->vehiculos_inspeccionados ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS INSPECCIONADAS: " . str_pad((string) ($totalesGenerales->personas_inspeccionadas ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "VEHÍCULOS IMPACTADOS: " . str_pad((string) ($totalesGenerales->vehiculos_impactados ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "PERSONAS IMPACTADAS: " . str_pad((string) ($totalesGenerales->personas_impactadas ?? 0), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "ANTECEDENTES DE PERSONAS: " . str_pad((string) ($totalesGenerales->antecedentes_personas ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ANTECEDENTES DE VEHÍCULOS: " . str_pad((string) ($totalesGenerales->antecedentes_vehiculos ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ANTECEDENTES DE MOTOS: " . str_pad((string) ($totalesGenerales->antecedentes_motos ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "ANTECEDENTES DE CAMIONES: " . str_pad((string) ($totalesGenerales->antecedentes_camiones ?? 0), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "PUESTAS A DISPOSICIÓN: " . str_pad((string) ($totalesGenerales->puestas_disposicion ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• VEHÍCULOS RECUPERADOS: " . str_pad((string) ($totalesGenerales->vehiculos_recuperados ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• ARMAS ASEGURADAS: " . str_pad((string) ($totalesGenerales->armas_aseguradas ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• MERCANCÍA RECUPERADA: " . str_pad((string) ($totalesGenerales->mercancia_recuperada ?? 0), 2, '0', STR_PAD_LEFT) . "\n";
        $texto .= "• DECOMISO DE DROGAS: " . str_pad((string) ($totalesGenerales->decomiso_drogas ?? 0), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "ESTADO DE FUERZA PARTICIPANTE: " . str_pad((string) ($totalesGenerales->estado_fuerza_participante ?? 0), 2, '0', STR_PAD_LEFT) . " elementos.\n";
        $texto .= "CRP´s. PARTICIPANTES: {$crpsGenerales}\n";
        $texto .= "KILÓMETROS RECORRIDOS: " . str_pad((string) ((float) ($totalesGenerales->kilometros_recorridos ?? 0)), 2, '0', STR_PAD_LEFT) . "\n\n";

        $texto .= "SE ANEXAN GRÁFICAS.\n\n";
        $texto .= "RESPETUOSAMENTE.";

        return $texto;
    }

    public function index(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();
        $fecha = $this->obtenerFechaFiltro($request);

        $dispositivos = OperativoDispositivo::with([
                'catalogo',
                'destacamento',
                'usuario',
                'fotos',
            ])
            ->where('operativo_id', $operativo->id)
            ->whereDate('fecha', $fecha)
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        $resumen = $this->obtenerResumenPorFecha($operativo->id, $fecha);

        return response()->json([
            'ok' => true,
            'operativo' => $operativo,
            'fecha' => $fecha,
            'dispositivos' => $dispositivos,
            'resumen' => $resumen,
        ]);
    }

    public function resumen(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();
        $fecha = $this->obtenerFechaFiltro($request);

        $resumen = $this->obtenerResumenPorFecha($operativo->id, $fecha);
        $totalesGenerales = $this->obtenerTotalesGeneralesPorFecha($operativo->id, $fecha);

        return response()->json([
            'ok' => true,
            'operativo' => $operativo,
            'fecha' => $fecha,
            'resumen' => $resumen,
            'totales_generales' => $totalesGenerales,
        ]);
    }

    public function whatsapp(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();
        $fecha = $this->obtenerFechaFiltro($request);

        $resumen = $this->obtenerResumenPorFecha($operativo->id, $fecha);
        $totalesGenerales = $this->obtenerTotalesGeneralesPorFecha($operativo->id, $fecha);

        $texto = $this->textoWhatsAppConsolidado($operativo, $fecha, $resumen, $totalesGenerales);

        return response()->json([
            'ok' => true,
            'fecha' => $fecha,
            'texto' => $texto,
            'url' => 'https://wa.me/?text=' . urlencode($texto),
        ]);
    }

    public function show()
    {
        return response()->json([
            'ok' => false,
            'message' => 'Esta ruta no aplica para el operativo principal. Usa el listado o el resumen.',
        ], 404);
    }

    public function edit()
    {
        return response()->json([
            'ok' => false,
            'message' => 'El operativo principal ya no se edita desde esta ruta. Solo se capturan dispositivos.',
        ], 405);
    }

    public function update(Request $request)
    {
        return response()->json([
            'ok' => false,
            'message' => 'El operativo principal ya no se actualiza desde esta ruta. Solo se capturan dispositivos.',
        ], 405);
    }
}
