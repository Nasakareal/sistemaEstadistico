<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\ActividadSubcategoria;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadPersona;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\LicenciaPuntoInfraccion;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ConduceLegalidadActividadSyncService
{
    private const TIPO_OPERATIVO = 'conduce_legalidad';
    private const SUBCATEGORIA = 'CONDUCE CON LEGALIDAD';

    /**
     * Crea o actualiza el espejo estadistico de una alimentacion hecha desde
     * Conduce con Legalidad. El vinculo unico captura.actividad_id hace que la
     * operacion sea idempotente, incluso al reintentar una solicitud movil.
     */
    public function sync(ConduceLegalidadCaptura $captura): ?Actividad
    {
        // Se recargan siempre porque el controlador puede haber reemplazado
        // fundamentos, vehiculos o personas dentro de esta misma transaccion.
        $captura->load([
            'operativo',
            'creador',
            'infraccion',
            'fundamentos.infraccion',
            'vehiculos',
            'personas',
            'actividad.vehiculos',
        ]);

        if (!$captura->operativo || $captura->operativo->tipo_operativo !== self::TIPO_OPERATIVO) {
            return null;
        }

        $subcategoria = $this->subcategoria();
        $actividad = $captura->actividad ?: new Actividad();
        $esNueva = !$actividad->exists;
        $infracciones = $this->infracciones($captura);

        $actividad->fill([
            'client_uuid' => $actividad->client_uuid ?: (string) Str::uuid(),
            'sync_status' => 'local',
            'sync_error' => null,
            'synced_at' => null,
            'actividad_categoria_id' => $subcategoria->actividad_categoria_id,
            'actividad_subcategoria_id' => $subcategoria->id,
            'nombre' => $this->nombreReportante($captura),
            'cantidad' => 1,
            'created_by' => $actividad->created_by ?: $captura->created_by,
            'updated_by' => $captura->created_by,
            'unidad_org_id' => $captura->unidad_id ?: $captura->operativo->unidad_id,
            'delegacion_id' => $captura->delegacion_id ?: $captura->operativo->delegacion_id,
            'destacamento_id' => $actividad->destacamento_id
                ?: optional($captura->creador)->destacamento_id,
            'fecha' => $captura->fecha ?: $captura->operativo->fecha,
            'hora' => $captura->hora ?: $captura->operativo->hora_inicio,
            'lugar' => $this->upper($captura->lugar ?: $captura->operativo->lugar),
            'municipio' => $this->upper($captura->municipio ?: $captura->operativo->municipio),
            'lat' => $captura->lat ?? $captura->operativo->lat,
            'lng' => $captura->lng ?? $captura->operativo->lng,
            'coordenadas_texto' => $this->nullable(
                $captura->coordenadas_texto ?: $captura->operativo->coordenadas_texto
            ),
            'fuente_ubicacion' => ($captura->lat !== null && $captura->lng !== null)
                ? 'GPS_APP'
                : $actividad->fuente_ubicacion,
            'motivo' => $this->motivo($captura),
            'narrativa' => $this->nullable($captura->narrativa),
            'observaciones' => $this->nullable($captura->observaciones),
            'infracciones_actividad' => $infracciones ?: null,
            'personas_alcanzadas' => 0,
            'personas_participantes' => 0,
            'personas_detenidas' => 0,
        ]);

        if ($esNueva) {
            $actividad->estado_revision = 'pendiente';
        }

        $actividad->save();

        if ((int) $captura->actividad_id !== (int) $actividad->id) {
            $captura->actividad_id = $actividad->id;
            $captura->save();
        }

        $vehiculos = $this->syncVehiculos($actividad, $captura);
        $this->syncPersonas($actividad, $captura, $vehiculos);

        return $actividad->fresh([
            'categoria',
            'subcategoria',
            'vehiculos',
            'personas',
        ]);
    }

    /**
     * Elimina la actividad espejo cuando la alimentacion fuente se elimina.
     * Debe llamarse despues de borrar la captura para no depender del cascade
     * de la llave foranea captura -> actividad.
     */
    public function deleteLinkedActivity(?Actividad $actividad): void
    {
        if (!$actividad || !$actividad->exists) {
            return;
        }

        $actividad->delete();
    }

    private function subcategoria(): ActividadSubcategoria
    {
        $subcategoria = ActividadSubcategoria::query()
            ->with('categoria')
            ->whereRaw('UPPER(TRIM(nombre)) = ?', [self::SUBCATEGORIA])
            ->first();

        if (!$subcategoria) {
            throw new RuntimeException(
                'No existe la subcategoria de actividad CONDUCE CON LEGALIDAD. Ejecuta las migraciones pendientes.'
            );
        }

        return $subcategoria;
    }

    private function nombreReportante(ConduceLegalidadCaptura $captura): string
    {
        $nombre = $this->nullable($captura->agente_nombre)
            ?: $this->nullable(optional($captura->creador)->nombre_completo)
            ?: $this->nullable(optional($captura->creador)->name)
            ?: 'ELEMENTO NO ESPECIFICADO';

        return $this->upper($nombre) ?: 'ELEMENTO NO ESPECIFICADO';
    }

    private function motivo(ConduceLegalidadCaptura $captura): string
    {
        $captura->loadMissing(['fundamentos.infraccion', 'infraccion']);
        $motivos = $captura->fundamentos
            ->map(fn ($fundamento) => $this->nullable(optional($fundamento->infraccion)->nombre)
                ?: $this->nullable($fundamento->infraccion_codigo))
            ->filter()
            ->unique()
            ->values();

        if ($motivos->isEmpty()) {
            $motivo = $this->nullable(optional($captura->infraccion)->nombre)
                ?: $this->nullable($captura->infraccion_codigo);
            if ($motivo !== null) {
                $motivos->push($motivo);
            }
        }

        return $this->upper($motivos->implode('; ')) ?: self::SUBCATEGORIA;
    }

    private function infracciones(ConduceLegalidadCaptura $captura): array
    {
        $captura->loadMissing(['fundamentos.infraccion', 'infraccion']);
        $filas = $captura->fundamentos;

        if ($filas->isEmpty() && $captura->licencia_punto_infraccion_id) {
            $filas = collect([(object) [
                'licencia_punto_infraccion_id' => $captura->licencia_punto_infraccion_id,
                'infraccion_codigo' => $captura->infraccion_codigo,
                'fundamento_legal' => $captura->fundamento_legal,
                'infraccion' => $captura->infraccion,
            ]]);
        }

        return $filas->map(function ($fila) {
            /** @var LicenciaPuntoInfraccion|null $catalogo */
            $catalogo = $fila->infraccion;

            return [
                'id' => (int) $fila->licencia_punto_infraccion_id,
                'codigo' => $this->nullable($fila->infraccion_codigo)
                    ?: $this->nullable(optional($catalogo)->codigo),
                'articulo' => $this->nullable(optional($catalogo)->articulo),
                'fraccion' => $this->nullable(optional($catalogo)->fraccion),
                'inciso' => $this->nullable(optional($catalogo)->inciso),
                'nombre' => $this->nullable(optional($catalogo)->nombre) ?: 'Infraccion',
                'etiqueta_operativa' => $this->nullable(optional($catalogo)->etiqueta_operativa),
                'texto_operativo' => $this->nullable(optional($catalogo)->texto_operativo),
                'descripcion' => $this->nullable(optional($catalogo)->descripcion),
                'fundamento_legal' => $this->nullable($fila->fundamento_legal)
                    ?: $this->nullable(optional($catalogo)->fundamento_legal),
                'referencia_legal_corta' => $this->nullable(optional($catalogo)->referencia_legal_corta),
                'resumen_sanciones' => $this->nullable(optional($catalogo)->resumen_sanciones),
                'retencion_vehiculo' => (bool) optional($catalogo)->retencion_vehiculo,
                'deposito_si_sin_persona_habilitada' => (bool) optional($catalogo)->deposito_si_sin_persona_habilitada,
            ];
        })->filter(fn (array $fila) => $fila['id'] > 0)->values()->all();
    }

    /** @return array<int, Vehiculo> */
    private function syncVehiculos(Actividad $actividad, ConduceLegalidadCaptura $captura): array
    {
        $existentes = $actividad->vehiculos()->orderBy('actividad_vehiculo.id')->get()->values();
        $sincronizados = [];

        foreach ($captura->vehiculos->values() as $index => $origen) {
            $vehiculo = $existentes->get($index) ?: new Vehiculo([
                'client_uuid' => (string) Str::uuid(),
            ]);
            $vehiculo->fill($this->vehiculoAttributes($origen));
            $vehiculo->save();
            $actividad->vehiculos()->syncWithoutDetaching([$vehiculo->id]);
            $this->syncServicioGrua($actividad, $vehiculo, $origen);
            $sincronizados[] = $vehiculo;
        }

        $idsConservados = collect($sincronizados)->pluck('id')->all();
        $idsSobrantes = $existentes->pluck('id')->diff($idsConservados)->values()->all();
        if ($idsSobrantes !== []) {
            $actividad->vehiculos()->detach($idsSobrantes);
        }

        return $sincronizados;
    }

    private function vehiculoAttributes(ConduceLegalidadVehiculo $origen): array
    {
        return [
            'marca' => $this->upperMax($origen->marca, 50) ?: 'NO ESPECIFICADA',
            'modelo' => $this->upperMax($origen->modelo, 10),
            'tipo' => $this->upperMax($origen->tipo ?: $origen->tipo_general, 50) ?: 'NO ESPECIFICADO',
            'linea' => $this->upperMax($origen->linea, 50) ?: 'NO ESPECIFICADA',
            'color' => $this->upperMax($origen->color, 30) ?: 'NO ESPECIFICADO',
            'placas' => $this->upperMax(str_replace('-', '', (string) $origen->placas), 15),
            'estado_placas' => $this->upperMax($origen->estado_placas, 30),
            'serie' => $this->upperMax(str_replace('-', '', (string) $origen->serie), 17),
            'capacidad_personas' => max(0, (int) $origen->capacidad_personas),
            'tipo_servicio' => $this->upperMax($origen->tipo_servicio, 50) ?: 'NO ESPECIFICADO',
            'tarjeta_circulacion_nombre' => $this->upperMax($origen->tarjeta_circulacion_nombre, 60),
            'grua_id' => $origen->grua_id,
            'numero_inventario_grua' => $this->upperMax($origen->numero_inventario, 100),
            'grua' => $this->upperMax($origen->grua, 255),
            'corralon' => $this->upperMax($origen->corralon, 255),
            'aseguradora' => $this->upperMax($origen->aseguradora, 100),
            'monto_danos' => $origen->monto_danos,
            'partes_danadas' => $this->upper($origen->partes_danadas),
            'antecedente_vehiculo' => (bool) $origen->antecedente_vehiculo,
        ];
    }

    private function syncServicioGrua(
        Actividad $actividad,
        Vehiculo $vehiculo,
        ConduceLegalidadVehiculo $origen
    ): void {
        if (!$origen->grua_id) {
            DB::table('servicios')->where('vehiculo_id', $vehiculo->id)->delete();
            return;
        }

        DB::table('servicios')->updateOrInsert(
            ['vehiculo_id' => $vehiculo->id],
            [
                'grua_id' => $origen->grua_id,
                'unidad_id' => $actividad->unidad_org_id ?: 1,
                'delegacion_id' => $actividad->delegacion_id,
                'tipo_vehiculo' => $this->upper($origen->tipo ?: $origen->tipo_general),
                'aseguradora' => $this->upper($origen->aseguradora) ?: '',
                'created_at' => $this->fechaHora($actividad),
                'updated_at' => now(),
            ]
        );
    }

    /** @param array<int, Vehiculo> $vehiculos */
    private function syncPersonas(
        Actividad $actividad,
        ConduceLegalidadCaptura $captura,
        array $vehiculos
    ): void {
        $actividad->personas()->delete();
        $vehiculo = $vehiculos[0] ?? null;

        foreach ($captura->personas as $persona) {
            $actividad->personas()->create($this->personaAttributes($persona, $vehiculo));
        }
    }

    private function personaAttributes(ConduceLegalidadPersona $persona, ?Vehiculo $vehiculo): array
    {
        $nombre = $this->nullable($persona->nombre)
            ?: collect([$persona->nombres, $persona->apellido_paterno, $persona->apellido_materno])
                ->map(fn ($parte) => $this->nullable($parte))
                ->filter()
                ->implode(' ');

        return [
            'vehiculo_id' => optional($vehiculo)->id,
            'tipo_participacion' => 'CONDUCTOR',
            'nombre' => $this->upperMax($nombre, 255) ?: 'PERSONA NO IDENTIFICADA',
            'telefono' => $this->max($persona->telefono, 30),
            'domicilio' => $this->upperMax($persona->domicilio, 255),
            'sexo' => $this->upperMax($persona->sexo, 30),
            'nacionalidad' => $this->upperMax($persona->nacionalidad, 80),
            'ocupacion' => $this->upperMax($persona->ocupacion, 255),
            'edad' => $persona->edad,
            'observaciones' => $this->upper($persona->observaciones),
        ];
    }

    private function fechaHora(Actividad $actividad): string
    {
        $fecha = optional($actividad->fecha)->format('Y-m-d') ?: now()->toDateString();
        $hora = $actividad->hora ?: '12:00:00';

        return $fecha . ' ' . $hora;
    }

    private function nullable($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function upper($value): ?string
    {
        $value = $this->nullable($value);

        return $value === null ? null : mb_strtoupper($value, 'UTF-8');
    }

    private function max($value, int $length): ?string
    {
        $value = $this->nullable($value);

        return $value === null ? null : mb_substr($value, 0, $length, 'UTF-8');
    }

    private function upperMax($value, int $length): ?string
    {
        $value = $this->upper($value);

        return $value === null ? null : mb_substr($value, 0, $length, 'UTF-8');
    }
}
