<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\ActividadSubcategoria;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\LicenciaPuntoInfraccion;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActividadConduceLegalidadSyncService
{
    private const SUBCATEGORIA = 'CONDUCE CON LEGALIDAD';
    private const FUNDAMENTOS_EXCLUIDOS = [
        'ART420_FIV_IA_B_TRANSPORTE_PUBLICO_ESCOLAR',
        'ART465_FXI_POLARIZADO_MAYOR_20',
        'ART519_FIV_IA_NO_MOVER_SINIESTRO_DANOS',
    ];
    private const TEXTOS_EXCLUIDOS = [
        'POLARIZADO',
        'TRANSPORTE PUBLICO',
        'SERVICIO PUBLICO',
        'SINIESTRO',
        'COMPETENCIAS DE VELOCIDAD',
        'PLACAS DE DEMOSTRACION',
        'SIN REGISTRO PREVIO EN REV',
        'PLACAS FORANEAS SIN REGISTRO PREVIO',
        'REGISTRO DE VISITA VENCIDO',
        'ESTACIONAR',
        'CERRAR U OBSTRUIR CIRCULACION',
        'OBSTRUIR CIRCULACION',
        'REPARACIONES A VEHICULOS',
        'REPARAR VEHICULO',
        'RESERVAR ESTACIONAMIENTO',
        'REQUERIMIENTO DE RETIRO',
        'ESPACIOS ESPECIALES DE ASCENSO',
        'ASCENSO DESCENSO TIEMPO EXCEDIDO',
    ];

    public function isConduceLegalidadSubcategoriaId($subcategoriaId): bool
    {
        $id = (int) ($subcategoriaId ?? 0);
        if ($id <= 0) {
            return false;
        }

        $nombre = ActividadSubcategoria::query()->whereKey($id)->value('nombre');
        return $this->normalizar($nombre) === self::SUBCATEGORIA;
    }

    public function assertCanSync(
        int $subcategoriaId,
        int $unidadId,
        ?int $delegacionId,
        array $fundamentos,
        int $vehiculosCount
    ): void {
        if (!$this->isConduceLegalidadSubcategoriaId($subcategoriaId)) {
            return;
        }

        if ($vehiculosCount > 1) {
            throw ValidationException::withMessages([
                'vehiculos' => 'Cada alimentación de Conduce con Legalidad admite únicamente un vehículo.',
            ]);
        }

        $this->fundamentosValidados($fundamentos);
        $this->operativoActivo($unidadId, $delegacionId);
    }

    public function sync(Actividad $actividad, array $fundamentos = []): ?ConduceLegalidadCaptura
    {
        if (!$this->isConduceLegalidadSubcategoriaId($actividad->actividad_subcategoria_id)) {
            $captura = $actividad->conduceLegalidadCaptura()->first();
            if ($captura) {
                $captura->delete();
            }
            return null;
        }

        $actividad->loadMissing('vehiculos');
        if ($actividad->vehiculos->count() > 1) {
            throw ValidationException::withMessages([
                'vehiculos' => 'Cada alimentación de Conduce con Legalidad admite únicamente un vehículo.',
            ]);
        }

        $captura = $actividad->conduceLegalidadCaptura()
            ->with('fundamentos')
            ->first();
        $selecciones = $fundamentos;
        if ($selecciones === [] && $captura) {
            $selecciones = $captura->fundamentos->map(fn ($item) => [
                'licencia_punto_infraccion_id' => $item->licencia_punto_infraccion_id,
                'infraccion_codigo' => $item->infraccion_codigo,
                'fundamento_legal' => $item->fundamento_legal,
            ])->all();
        }
        $infracciones = $this->fundamentosValidados($selecciones);

        $operativo = $captura
            ? $captura->operativo
            : $this->operativoActivo(
                (int) $actividad->unidad_org_id,
                $actividad->delegacion_id ? (int) $actividad->delegacion_id : null
            );
        $primera = $infracciones[0];
        $primerSnapshot = $this->snapshot($primera, $selecciones[0] ?? []);

        if (!$captura) {
            $captura = new ConduceLegalidadCaptura();
            $captura->operativo_id = $operativo->id;
            $captura->actividad_id = $actividad->id;
            $captura->client_uuid = 'actividad-' . $actividad->id;
        }

        $captura->fill([
            'licencia_punto_infraccion_id' => $primera->id,
            'infraccion_codigo' => $primerSnapshot['codigo'],
            'fundamento_legal' => $primerSnapshot['fundamento_legal'],
            'created_by' => $actividad->created_by,
            'unidad_id' => $actividad->unidad_org_id,
            'delegacion_id' => $actividad->delegacion_id,
            'fecha' => $actividad->fecha,
            'hora' => $actividad->hora,
            'municipio' => $actividad->municipio,
            'lugar' => $actividad->lugar,
            'lat' => $actividad->lat,
            'lng' => $actividad->lng,
            'coordenadas_texto' => $actividad->coordenadas_texto,
            'narrativa' => $actividad->narrativa,
            'observaciones' => $actividad->observaciones,
        ]);
        $captura->save();

        $captura->fundamentos()->delete();
        foreach ($infracciones as $index => $infraccion) {
            $snapshot = $this->snapshot($infraccion, $selecciones[$index] ?? []);
            $captura->fundamentos()->create([
                'licencia_punto_infraccion_id' => $infraccion->id,
                'orden' => $index,
                'infraccion_codigo' => $snapshot['codigo'],
                'fundamento_legal' => $snapshot['fundamento_legal'],
            ]);
        }

        $captura->vehiculos()->delete();
        $vehiculo = $actividad->vehiculos->first();
        if ($vehiculo) {
            $captura->vehiculos()->create([
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'tipo_general' => $this->tipoGeneralVehiculo($vehiculo->tipo),
                'tipo' => $vehiculo->tipo,
                'linea' => $vehiculo->linea,
                'color' => $vehiculo->color,
                'placas' => $vehiculo->placas,
                'estado_placas' => $vehiculo->estado_placas,
                'serie' => $vehiculo->serie,
                'capacidad_personas' => $vehiculo->capacidad_personas,
                'tipo_servicio' => $vehiculo->tipo_servicio,
                'tarjeta_circulacion_nombre' => $vehiculo->tarjeta_circulacion_nombre,
                'grua_id' => $vehiculo->grua_id,
                'corralon_id' => $vehiculo->corralon_id,
                'grua' => $vehiculo->grua,
                'corralon' => $vehiculo->corralon,
                'servicio_unidad_id' => $actividad->unidad_org_id,
                'servicio_delegacion_id' => $actividad->delegacion_id,
                'servicio_created_by' => $actividad->created_by,
                'aseguradora' => $vehiculo->aseguradora,
                'monto_danos' => $vehiculo->monto_danos,
                'partes_danadas' => $vehiculo->partes_danadas,
                'antecedente_vehiculo' => $vehiculo->antecedente_vehiculo,
                'licencia_punto_infraccion_id' => $primera->id,
                'infraccion_codigo' => $primerSnapshot['codigo'],
                'fundamento_legal' => $primerSnapshot['fundamento_legal'],
                'retencion_vehiculo' => true,
                'motivo_retencion' => $primera->nombre,
            ]);
        }

        return $captura->fresh();
    }

    private function operativoActivo(int $unidadId, ?int $delegacionId): ConduceLegalidadOperativo
    {
        $query = ConduceLegalidadOperativo::query()
            ->where('tipo_operativo', 'conduce_legalidad')
            ->where('estado', 'activo')
            ->where('unidad_id', $unidadId);

        if ($delegacionId === null) {
            $query->whereNull('delegacion_id');
        } else {
            $query->where('delegacion_id', $delegacionId);
        }

        $operativo = $query->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->orderByDesc('id')
            ->first();

        if (!$operativo) {
            throw ValidationException::withMessages([
                'actividad_subcategoria_id' =>
                    'No hay un operativo activo de Conduce con Legalidad para tu unidad'
                    . ($delegacionId ? ' y delegación.' : '.')
                    . ' Primero crea o abre el operativo correspondiente.',
            ]);
        }

        return $operativo;
    }

    /**
     * @return array<int, LicenciaPuntoInfraccion>
     */
    private function fundamentosValidados(array $selecciones): array
    {
        if ($selecciones === []) {
            throw ValidationException::withMessages([
                'conduce_legalidad_fundamentos' =>
                    'Selecciona al menos un fundamento legal para Conduce con Legalidad.',
            ]);
        }

        $result = [];
        $seen = [];
        foreach (array_values($selecciones) as $seleccion) {
            $id = (int) Arr::get($seleccion, 'licencia_punto_infraccion_id', 0);
            $infraccion = LicenciaPuntoInfraccion::activas()->find($id);
            if (!$infraccion
                || (!(bool) $infraccion->retencion_vehiculo
                    && !(bool) $infraccion->deposito_si_sin_persona_habilitada)
                || $this->estaExcluido($infraccion)) {
                throw ValidationException::withMessages([
                    'conduce_legalidad_fundamentos' =>
                        'Uno de los fundamentos no corresponde a Conduce con Legalidad.',
                ]);
            }

            $snapshot = $this->snapshot($infraccion, is_array($seleccion) ? $seleccion : []);
            $key = $infraccion->id . '|' . ($snapshot['codigo'] ?? '');
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'conduce_legalidad_fundamentos' =>
                        'No puedes seleccionar dos veces el mismo fundamento.',
                ]);
            }
            $seen[$key] = true;
            $result[] = $infraccion;
        }

        return $result;
    }

    private function snapshot(LicenciaPuntoInfraccion $infraccion, array $seleccion): array
    {
        $base = $this->nullable($infraccion->codigo);
        $requested = $this->nullable($seleccion['infraccion_codigo'] ?? null);
        $codigo = $requested !== null && $base !== null && Str::startsWith($requested, $base)
            ? $requested
            : $base;
        $legal = $requested !== null && $requested === $codigo
            ? $this->nullable($seleccion['fundamento_legal'] ?? null)
            : null;

        return [
            'codigo' => $codigo,
            'fundamento_legal' => $legal ?: $this->nullable($infraccion->fundamento_legal),
        ];
    }

    private function estaExcluido(LicenciaPuntoInfraccion $infraccion): bool
    {
        $codigo = Str::upper(trim((string) $infraccion->codigo));
        if (in_array($codigo, self::FUNDAMENTOS_EXCLUIDOS, true)) {
            return true;
        }
        $texto = $this->normalizar(implode(' ', array_filter([
            $infraccion->codigo,
            $infraccion->nombre,
            $infraccion->etiqueta_operativa,
            $infraccion->texto_operativo,
            $infraccion->descripcion,
            $infraccion->fundamento_legal,
        ])));
        return Str::contains($texto, self::TEXTOS_EXCLUIDOS);
    }

    private function normalizar($value): string
    {
        return preg_replace('/\s+/', ' ', Str::upper(Str::ascii(trim((string) $value)))) ?: '';
    }

    private function nullable($value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }

    private function tipoGeneralVehiculo($tipo): string
    {
        $normalizado = $this->normalizar($tipo);
        if (Str::contains($normalizado, ['MOTO', 'CUATRIMOTO'])) {
            return 'motocicleta';
        }
        if (Str::contains($normalizado, ['BICICLETA', 'TRICICLO', 'NO MOTORIZADO'])) {
            return 'no_motorizado';
        }
        if (Str::contains($normalizado, ['CAMION', 'TRACTO', 'REMOLQUE', 'CARGA'])) {
            return 'camion';
        }
        if (Str::contains($normalizado, ['AUTOMOVIL', 'SEDAN', 'COUPE', 'HATCHBACK', 'PICKUP', 'SUV'])) {
            return 'automovil';
        }

        return 'motocicleta';
    }
}
