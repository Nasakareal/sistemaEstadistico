<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destacamento;
use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\OperativoDispositivo;
use App\Models\OperativoDispositivoCatalogo;
use App\Models\OperativoDispositivoFoto;
use App\Models\Vehiculo;
use App\Services\GuardianesCaminoRevisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuardianesCaminoDispositivoController extends Controller
{
    protected function obtenerOperativoUnico()
    {
        $catalogo = OperativoCatalogo::where('slug', config('guardianes_camino.operativo_slug', 'guardianes-del-camino'))->firstOrFail();

        $operativo = Operativo::with('catalogo')
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
            $operativo->created_by = Auth::id();
            $operativo->updated_by = Auth::id();
            $operativo->save();

            $operativo->load('catalogo');
        }

        return $operativo;
    }

    protected function obtenerCatalogos()
    {
        return OperativoDispositivoCatalogo::query()
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    protected function obtenerDestacamentos()
    {
        return Destacamento::query()
            ->orderBy('nombre')
            ->get();
    }

    protected function obtenerDatosVista(): array
    {
        return [
            'dispositivosConfig' => config('guardianes_camino.dispositivos', []),
            'allCampos' => config('guardianes_camino.all_campos', []),
        ];
    }

    protected function reglasValidacion(bool $updating = false): array
    {
        $base = $updating ? 'sometimes|required' : 'required';

        return [
            'operativo_dispositivo_catalogo_id' => [$base, 'integer', 'exists:operativo_dispositivo_catalogos,id'],
            'client_uuid' => ['nullable', 'string', 'max:100'],
            'fecha' => [$base, 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i'],

            'unidad_org_id' => ['nullable', 'integer'],
            'delegacion_id' => ['nullable', 'integer'],
            'destacamento_id' => ['nullable', 'integer', 'exists:destacamentos,id'],

            'tipo_reporte' => ['nullable', 'string', 'max:100'],
            'asunto' => ['nullable', 'string', 'max:255'],

            'lugar' => ['nullable', 'string', 'max:255'],
            'carretera' => ['nullable', 'string', 'max:255'],
            'tramo' => ['nullable', 'string', 'max:255'],
            'kilometro' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'coordenadas_texto' => ['nullable', 'string', 'max:100'],

            'descripcion' => ['nullable', 'string'],
            'narrativa' => ['nullable', 'string'],
            'acciones_realizadas' => ['nullable', 'string'],
            'frase_institucional' => ['nullable', 'string'],

            'nombre_conductor' => ['nullable', 'string', 'max:255'],
            'ocupacion_conductor' => ['nullable', 'string', 'max:255'],
            'acompanantes_cantidad' => ['nullable', 'integer', 'min:0'],
            'vehiculo_descripcion' => ['nullable', 'string'],
            'placas_apoyado' => ['nullable', 'string', 'max:50'],
            'procedencia' => ['nullable', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
            'motivo_apoyo' => ['nullable', 'string'],

            'cantidad' => ['nullable', 'integer', 'min:0'],
            'vehiculos_inspeccionados' => ['nullable', 'integer', 'min:0'],
            'personas_inspeccionadas' => ['nullable', 'integer', 'min:0'],
            'vehiculos_impactados' => ['nullable', 'integer', 'min:0'],
            'personas_impactadas' => ['nullable', 'integer', 'min:0'],
            'estado_fuerza_participante' => ['nullable', 'integer', 'min:0'],
            'kilometros_recorridos' => ['nullable', 'numeric', 'min:0'],
            'crps_participantes' => ['nullable', 'string'],
            'elementos_participantes_texto' => ['nullable', 'string'],
            'cargo_responsable' => ['nullable', 'string', 'max:255'],
            'nombre_responsable' => ['nullable', 'string', 'max:255'],

            'acompanamientos' => ['nullable', 'integer', 'min:0'],
            'abanderamientos' => ['nullable', 'integer', 'min:0'],
            'auxilios_viales' => ['nullable', 'integer', 'min:0'],
            'tipo_acompanamiento' => ['nullable', 'string', 'max:255'],
            'tipo_abanderamiento' => ['nullable', 'string', 'max:255'],
            'tipo_auxilio_vial' => ['nullable', 'string', 'max:255'],
            'folio_atendido' => ['nullable', 'string', 'max:255'],
            'motivo_folio' => ['nullable', 'string'],

            'prox_empresas' => ['nullable', 'integer', 'min:0'],
            'prox_tiendas_conveniencia' => ['nullable', 'integer', 'min:0'],
            'prox_escuelas' => ['nullable', 'integer', 'min:0'],
            'prox_hospitales' => ['nullable', 'integer', 'min:0'],

            'antecedentes_personas' => ['nullable', 'integer', 'min:0'],
            'antecedentes_vehiculos' => ['nullable', 'integer', 'min:0'],
            'antecedentes_motos' => ['nullable', 'integer', 'min:0'],
            'antecedentes_camiones' => ['nullable', 'integer', 'min:0'],

            'puestas_disposicion' => ['nullable', 'integer', 'min:0'],
            'vehiculos_recuperados' => ['nullable', 'integer', 'min:0'],
            'armas_aseguradas' => ['nullable', 'integer', 'min:0'],
            'mercancia_recuperada' => ['nullable', 'integer', 'min:0'],
            'decomiso_drogas' => ['nullable', 'integer', 'min:0'],

            'requiere_evidencia' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string'],

            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'fotos_observaciones' => ['nullable', 'array'],
            'fotos_observaciones.*' => ['nullable', 'string', 'max:255'],
            'fotos_caption' => ['nullable', 'array'],
            'fotos_caption.*' => ['nullable', 'string', 'max:255'],

            'vehiculos' => ['nullable', 'array'],
            'vehiculos.*' => ['integer', 'exists:vehiculos,id'],
            'vehiculos_nuevos' => ['nullable', 'array'],
            'vehiculos_nuevos.*.rol' => ['nullable', 'string', 'max:100'],
            'vehiculos_nuevos.*.observaciones' => ['nullable', 'string', 'max:255'],
            'vehiculos_nuevos.*.marca' => ['required_with:vehiculos_nuevos', 'string', 'max:50'],
            'vehiculos_nuevos.*.modelo' => ['nullable', 'string', 'max:10'],
            'vehiculos_nuevos.*.tipo_general' => ['nullable', 'string', 'max:50'],
            'vehiculos_nuevos.*.tipo' => ['required_with:vehiculos_nuevos', 'string', 'max:50'],
            'vehiculos_nuevos.*.linea' => ['required_with:vehiculos_nuevos', 'string', 'max:50'],
            'vehiculos_nuevos.*.color' => ['required_with:vehiculos_nuevos', 'string', 'max:30'],
            'vehiculos_nuevos.*.placas' => ['nullable', 'string', 'max:15', 'regex:/^[A-Z0-9]{5,15}$/i'],
            'vehiculos_nuevos.*.estado_placas' => ['nullable', 'string', 'max:30'],
            'vehiculos_nuevos.*.serie' => ['nullable', 'string', 'max:17', 'regex:/^[A-Z0-9]{6,17}$/i'],
            'vehiculos_nuevos.*.capacidad_personas' => ['required_with:vehiculos_nuevos', 'integer', 'min:0'],
            'vehiculos_nuevos.*.tipo_servicio' => ['required_with:vehiculos_nuevos', 'string', 'max:50'],
            'vehiculos_nuevos.*.tarjeta_circulacion_nombre' => ['nullable', 'string', 'max:60'],
            'vehiculos_nuevos.*.grua' => ['nullable', 'string', 'max:255'],
            'vehiculos_nuevos.*.corralon' => ['nullable', 'string', 'max:255'],
            'vehiculos_nuevos.*.aseguradora' => ['nullable', 'string', 'max:100'],
            'vehiculos_nuevos.*.antecedente_vehiculo' => ['nullable', 'boolean'],
            'vehiculos_nuevos.*.monto_danos' => ['nullable', 'numeric', 'min:0'],
            'vehiculos_nuevos.*.partes_danadas' => ['nullable', 'string'],

            'personas' => ['nullable', 'array'],
            'personas.*.nombre' => ['nullable', 'string', 'max:255'],
            'personas.*.tipo_participacion' => ['nullable', 'string', 'max:100'],
            'personas.*.curp' => ['nullable', 'string', 'max:30'],
            'personas.*.telefono' => ['nullable', 'digits:10'],
            'personas.*.domicilio' => ['nullable', 'string', 'max:255'],
            'personas.*.sexo' => ['nullable', 'string', 'in:MASCULINO,FEMENINO,OTRO'],
            'personas.*.ocupacion' => ['nullable', 'string', 'max:255'],
            'personas.*.edad' => ['nullable', 'integer', 'min:0', 'max:120'],
            'personas.*.tipo_licencia' => ['nullable', 'string', 'max:50'],
            'personas.*.estado_licencia' => ['nullable', 'string', 'max:100'],
            'personas.*.vigencia_licencia' => ['nullable', 'date'],
            'personas.*.numero_licencia' => ['nullable', 'string', 'max:50'],
            'personas.*.permanente' => ['nullable', 'boolean'],
            'personas.*.cinturon' => ['nullable', 'boolean'],
            'personas.*.antecedentes' => ['nullable', 'boolean'],
            'personas.*.certificado_lesiones' => ['nullable', 'boolean'],
            'personas.*.certificado_alcoholemia' => ['nullable', 'boolean'],
            'personas.*.aliento_etilico' => ['nullable', 'boolean'],
            'personas.*.observaciones' => ['nullable', 'string'],
        ];
    }

    protected function inyectarDatosOrganizacion(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        if (!$user->unidad_id) {
            throw ValidationException::withMessages([
                'unidad_org_id' => 'Tu usuario no tiene una unidad asignada.',
            ]);
        }

        $request->merge([
            'unidad_org_id' => $user->unidad_id,
            'delegacion_id' => $user->delegacion_id ?: $request->input('delegacion_id'),
            'destacamento_id' => $user->destacamento_id ?: $request->input('destacamento_id'),
        ]);
    }

    protected function validarDatosOrganizacionFinal(array $data): void
    {
        if (empty($data['unidad_org_id'])) {
            throw ValidationException::withMessages([
                'unidad_org_id' => 'No fue posible determinar la unidad del usuario autenticado.',
            ]);
        }

        if (empty($data['delegacion_id']) && empty($data['destacamento_id'])) {
            throw ValidationException::withMessages([
                'destacamento_id' => 'Debes tener delegación o destacamento asignado, o capturar el destacamento en el formulario.',
            ]);
        }
    }

    protected function normalizarNombre(?string $valor): string
    {
        $valor = (string) $valor;
        $valor = mb_strtoupper(trim($valor), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);

        return $ascii !== false ? $ascii : $valor;
    }

    protected function obtenerConfigDispositivoPorNombre(?string $nombre): ?array
    {
        $nombreNormalizado = $this->normalizarNombre($nombre);

        if ($nombreNormalizado === '') {
            return null;
        }

        foreach (config('guardianes_camino.dispositivos', []) as $clave => $item) {
            if (!is_array($item)) {
                continue;
            }

            $candidatos = array_filter(array_merge(
                [$clave, $item['nombre'] ?? null, $item['titulo'] ?? null],
                $item['aliases'] ?? []
            ));

            foreach ($candidatos as $candidato) {
                if ($this->normalizarNombre($candidato) === $nombreNormalizado) {
                    return $item;
                }
            }
        }

        return null;
    }

    protected function obtenerCamposConfigPorCatalogo(?int $catalogoId): array
    {
        if (!$catalogoId) {
            return [];
        }

        $catalogo = OperativoDispositivoCatalogo::find($catalogoId);

        if (!$catalogo) {
            return [];
        }

        $config = $this->obtenerConfigDispositivoPorNombre($catalogo->nombre);

        return is_array($config) ? ($config['campos'] ?? []) : [];
    }

    protected function valorCampoNoAplicable(string $campo)
    {
        $camposTexto = [
            'crps_participantes',
            'tipo_acompanamiento',
            'tipo_abanderamiento',
            'tipo_auxilio_vial',
            'folio_atendido',
            'motivo_folio',
        ];

        return in_array($campo, $camposTexto, true) ? null : 0;
    }

    protected function limpiarCamposNoAplicables(array &$data, int $catalogoId): void
    {
        $allCampos = config('guardianes_camino.all_campos', []);
        $camposActivos = $this->obtenerCamposConfigPorCatalogo($catalogoId);

        if (empty($allCampos) || empty($camposActivos)) {
            return;
        }

        $camposExtras = [
            'puestas_disposicion',
            'vehiculos_recuperados',
            'armas_aseguradas',
            'mercancia_recuperada',
            'decomiso_drogas',
            'antecedentes_personas',
            'antecedentes_vehiculos',
            'antecedentes_motos',
            'antecedentes_camiones',
        ];

        $camposPermitidos = array_unique(array_merge($camposActivos, $camposExtras));

        foreach ($allCampos as $campo) {
            if (!in_array($campo, $camposPermitidos, true)) {
                $data[$campo] = $this->valorCampoNoAplicable($campo);
            }
        }
    }

    protected function llenarDispositivo(OperativoDispositivo $dispositivo, Operativo $operativo, array $data, Request $request): void
    {
        $destacamento = !empty($data['destacamento_id'])
            ? Destacamento::find($data['destacamento_id'])
            : null;

        $dispositivo->client_uuid = $data['client_uuid'] ?? $dispositivo->client_uuid ?? (string) Str::uuid();
        $dispositivo->sync_status = 'synced';
        $dispositivo->sync_error = null;
        $dispositivo->synced_at = now();

        $dispositivo->operativo_id = $operativo->id;
        $dispositivo->operativo_dispositivo_catalogo_id = $data['operativo_dispositivo_catalogo_id'] ?? $dispositivo->operativo_dispositivo_catalogo_id;

        $dispositivo->tipo_reporte = $data['tipo_reporte'] ?? null;
        $dispositivo->asunto = $data['asunto'] ?? null;

        if (array_key_exists('fecha', $data)) {
            $dispositivo->fecha = $data['fecha'];
        }
        if (array_key_exists('hora', $data)) {
            $dispositivo->hora = $data['hora'];
        }
        if (array_key_exists('hora_inicio', $data)) {
            $dispositivo->hora_inicio = $data['hora_inicio'];
        }
        if (array_key_exists('hora_fin', $data)) {
            $dispositivo->hora_fin = $data['hora_fin'];
        }

        $dispositivo->unidad_org_id = $data['unidad_org_id'] ?? $dispositivo->unidad_org_id;
        $dispositivo->delegacion_id = $data['delegacion_id'] ?? null;
        $dispositivo->destacamento_id = $data['destacamento_id'] ?? null;
        $dispositivo->user_id = $dispositivo->exists ? $dispositivo->user_id : $request->user()->id;

        $dispositivo->lugar = $data['lugar'] ?? null;
        $dispositivo->carretera = $data['carretera'] ?? null;
        $dispositivo->tramo = $data['tramo'] ?? null;
        $dispositivo->kilometro = $data['kilometro'] ?? null;
        $dispositivo->lat = $data['lat'] ?? null;
        $dispositivo->lng = $data['lng'] ?? null;
        $dispositivo->coordenadas_texto = $data['coordenadas_texto'] ?? null;

        $dispositivo->descripcion = $data['descripcion'] ?? null;
        $dispositivo->narrativa = $data['narrativa'] ?? null;
        $dispositivo->acciones_realizadas = $data['acciones_realizadas'] ?? null;
        $dispositivo->frase_institucional = $data['frase_institucional'] ?? null;

        $dispositivo->nombre_conductor = $data['nombre_conductor'] ?? null;
        $dispositivo->ocupacion_conductor = $data['ocupacion_conductor'] ?? null;
        $dispositivo->acompanantes_cantidad = $data['acompanantes_cantidad'] ?? 0;
        $dispositivo->vehiculo_descripcion = $data['vehiculo_descripcion'] ?? null;
        $dispositivo->placas_apoyado = $data['placas_apoyado'] ?? null;
        $dispositivo->procedencia = $data['procedencia'] ?? null;
        $dispositivo->destino = $data['destino'] ?? null;
        $dispositivo->motivo_apoyo = $data['motivo_apoyo'] ?? null;

        $dispositivo->cantidad = 1;
        $dispositivo->vehiculos_inspeccionados = $data['vehiculos_inspeccionados'] ?? 0;
        $dispositivo->personas_inspeccionadas = $data['personas_inspeccionadas'] ?? 0;
        $dispositivo->vehiculos_impactados = $data['vehiculos_impactados'] ?? 0;
        $dispositivo->personas_impactadas = $data['personas_impactadas'] ?? 0;
        $dispositivo->estado_fuerza_participante = $data['estado_fuerza_participante'] ?? 0;
        $dispositivo->kilometros_recorridos = $data['kilometros_recorridos'] ?? 0;
        $dispositivo->crps_participantes = $data['crps_participantes'] ?? null;
        $dispositivo->elementos_participantes_texto = $data['elementos_participantes_texto'] ?? null;
        $dispositivo->cargo_responsable = $data['cargo_responsable'] ?? null;
        $dispositivo->nombre_responsable = $data['nombre_responsable'] ?? null;
        $dispositivo->destacamento_nombre_snapshot = $destacamento->nombre ?? null;

        $dispositivo->acompanamientos = $data['acompanamientos'] ?? 0;
        $dispositivo->abanderamientos = $data['abanderamientos'] ?? 0;
        $dispositivo->auxilios_viales = $data['auxilios_viales'] ?? 0;
        $dispositivo->tipo_acompanamiento = $data['tipo_acompanamiento'] ?? null;
        $dispositivo->tipo_abanderamiento = $data['tipo_abanderamiento'] ?? null;
        $dispositivo->tipo_auxilio_vial = $data['tipo_auxilio_vial'] ?? null;
        $dispositivo->folio_atendido = $data['folio_atendido'] ?? null;
        $dispositivo->motivo_folio = $data['motivo_folio'] ?? null;

        $dispositivo->prox_empresas = $data['prox_empresas'] ?? 0;
        $dispositivo->prox_tiendas_conveniencia = $data['prox_tiendas_conveniencia'] ?? 0;
        $dispositivo->prox_escuelas = $data['prox_escuelas'] ?? 0;
        $dispositivo->prox_hospitales = $data['prox_hospitales'] ?? 0;

        $dispositivo->antecedentes_personas = $data['antecedentes_personas'] ?? 0;
        $dispositivo->antecedentes_vehiculos = $data['antecedentes_vehiculos'] ?? 0;
        $dispositivo->antecedentes_motos = $data['antecedentes_motos'] ?? 0;
        $dispositivo->antecedentes_camiones = $data['antecedentes_camiones'] ?? 0;

        $dispositivo->puestas_disposicion = $data['puestas_disposicion'] ?? 0;
        $dispositivo->vehiculos_recuperados = $data['vehiculos_recuperados'] ?? 0;
        $dispositivo->armas_aseguradas = $data['armas_aseguradas'] ?? 0;
        $dispositivo->mercancia_recuperada = $data['mercancia_recuperada'] ?? 0;
        $dispositivo->decomiso_drogas = $data['decomiso_drogas'] ?? 0;

        $dispositivo->requiere_evidencia = (bool) ($data['requiere_evidencia'] ?? false);
        $dispositivo->observaciones = $data['observaciones'] ?? null;

        if (!$dispositivo->exists) {
            $dispositivo->created_by = $request->user()->id;
            $dispositivo->estado_revision = OperativoDispositivo::REVISION_PENDIENTE;
            $dispositivo->revisado_por = null;
            $dispositivo->revisado_at = null;
            $dispositivo->observacion_revision = null;
        }

        $dispositivo->updated_by = $request->user()->id;
    }

    protected function guardarFotos(Request $request, OperativoDispositivo $dispositivo): void
    {
        if (!$request->hasFile('fotos')) {
            return;
        }

        $observaciones = $request->input('fotos_observaciones', []);
        $captions = $request->input('fotos_caption', []);

        $ultimoOrden = (int) OperativoDispositivoFoto::where('operativo_dispositivo_id', $dispositivo->id)->max('orden');

        foreach ((array) $request->file('fotos') as $index => $archivo) {
            if (!$archivo || !$archivo->isValid()) {
                continue;
            }

            $ruta = $archivo->store('guardianes_camino/dispositivos/' . $dispositivo->id, 'public');

            $foto = new OperativoDispositivoFoto();
            $foto->client_uuid = (string) Str::uuid();
            $foto->operativo_dispositivo_id = $dispositivo->id;
            $foto->ruta = $ruta;
            $foto->nombre_original = $archivo->getClientOriginalName();
            $foto->mime_type = $archivo->getClientMimeType();
            $foto->peso = $archivo->getSize();
            $foto->observaciones = $observaciones[$index] ?? null;
            $foto->sync_status = 'synced';
            $foto->sync_error = null;
            $foto->synced_at = now();
            $foto->orden = $ultimoOrden + $index + 1;
            $foto->es_portada = ($foto->orden === 1);
            $foto->caption = $captions[$index] ?? null;
            $foto->lat = $dispositivo->lat;
            $foto->lng = $dispositivo->lng;
            $foto->tomada_en = now();
            $foto->incluida_en_compartido = true;
            $foto->created_by = $request->user()->id;
            $foto->save();
        }
    }

    protected function normalizarTextoRelacionado($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        return mb_strtoupper($texto, 'UTF-8');
    }

    protected function normalizarTokenRelacionado($valor): ?string
    {
        $texto = $this->normalizarTextoRelacionado($valor);

        if ($texto === null) {
            return null;
        }

        $texto = preg_replace('/[\s\-\_\.,]+/u', '', $texto);

        return $texto !== '' ? $texto : null;
    }

    protected function normalizarEstadoPlacasRelacionado($valor): ?string
    {
        $texto = $this->normalizarTokenRelacionado($valor);

        if ($texto === null) {
            return null;
        }

        return mb_substr($texto, 0, 15, 'UTF-8');
    }

    protected function boolRelacionado($valor): bool
    {
        $bool = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($bool !== null) {
            return $bool;
        }

        return (int) $valor === 1;
    }

    protected function vehiculoNuevoPayload(array $vehiculo): array
    {
        return [
            'client_uuid' => (string) Str::uuid(),
            'marca' => $this->normalizarTextoRelacionado($vehiculo['marca'] ?? null),
            'modelo' => $this->normalizarTextoRelacionado($vehiculo['modelo'] ?? null),
            'tipo' => $this->normalizarTextoRelacionado($vehiculo['tipo'] ?? null),
            'linea' => $this->normalizarTextoRelacionado($vehiculo['linea'] ?? null),
            'color' => $this->normalizarTextoRelacionado($vehiculo['color'] ?? null),
            'placas' => $this->normalizarTokenRelacionado($vehiculo['placas'] ?? null),
            'estado_placas' => $this->normalizarEstadoPlacasRelacionado($vehiculo['estado_placas'] ?? null),
            'serie' => $this->normalizarTokenRelacionado($vehiculo['serie'] ?? null),
            'capacidad_personas' => (int) ($vehiculo['capacidad_personas'] ?? 0),
            'tipo_servicio' => $this->normalizarTextoRelacionado($vehiculo['tipo_servicio'] ?? null),
            'tarjeta_circulacion_nombre' => $this->normalizarTextoRelacionado($vehiculo['tarjeta_circulacion_nombre'] ?? null),
            'grua' => $this->normalizarTextoRelacionado($vehiculo['grua'] ?? null),
            'corralon' => $this->normalizarTextoRelacionado($vehiculo['corralon'] ?? null),
            'aseguradora' => $this->normalizarTextoRelacionado($vehiculo['aseguradora'] ?? null),
            'fotos' => null,
            'antecedente_vehiculo' => $this->boolRelacionado($vehiculo['antecedente_vehiculo'] ?? false) ? 1 : 0,
            'monto_danos' => $vehiculo['monto_danos'] ?? null,
            'partes_danadas' => $this->normalizarTextoRelacionado($vehiculo['partes_danadas'] ?? null),
        ];
    }

    protected function crearVehiculosNuevos(OperativoDispositivo $dispositivo, Request $request): void
    {
        foreach ((array) $request->input('vehiculos_nuevos', []) as $vehiculo) {
            if (!is_array($vehiculo) || empty($vehiculo['marca'])) {
                continue;
            }

            $nuevo = Vehiculo::create($this->vehiculoNuevoPayload($vehiculo));

            $dispositivo->vehiculos()->attach($nuevo->id, [
                'rol' => $this->normalizarTextoRelacionado($vehiculo['rol'] ?? null),
                'observaciones' => $this->normalizarTextoRelacionado($vehiculo['observaciones'] ?? null),
            ]);
        }
    }

    protected function personaRelacionadaPayload(array $persona): array
    {
        return [
            'nombre' => $this->normalizarTextoRelacionado($persona['nombre'] ?? null),
            'tipo_participacion' => $this->normalizarTextoRelacionado($persona['tipo_participacion'] ?? null),
            'curp' => $this->normalizarTokenRelacionado($persona['curp'] ?? null),
            'telefono' => $this->normalizarTokenRelacionado($persona['telefono'] ?? null),
            'domicilio' => $this->normalizarTextoRelacionado($persona['domicilio'] ?? null),
            'sexo' => $this->normalizarTextoRelacionado($persona['sexo'] ?? null),
            'ocupacion' => $this->normalizarTextoRelacionado($persona['ocupacion'] ?? null),
            'edad' => array_key_exists('edad', $persona) && $persona['edad'] !== '' ? (int) $persona['edad'] : null,
            'tipo_licencia' => $this->normalizarTextoRelacionado($persona['tipo_licencia'] ?? null),
            'estado_licencia' => $this->normalizarTextoRelacionado($persona['estado_licencia'] ?? null),
            'vigencia_licencia' => $persona['vigencia_licencia'] ?? null,
            'numero_licencia' => $this->normalizarTextoRelacionado($persona['numero_licencia'] ?? null),
            'permanente' => $this->boolRelacionado($persona['permanente'] ?? false),
            'cinturon' => $this->boolRelacionado($persona['cinturon'] ?? false),
            'antecedentes' => $this->boolRelacionado($persona['antecedentes'] ?? false),
            'certificado_lesiones' => $this->boolRelacionado($persona['certificado_lesiones'] ?? false),
            'certificado_alcoholemia' => $this->boolRelacionado($persona['certificado_alcoholemia'] ?? false),
            'aliento_etilico' => $this->boolRelacionado($persona['aliento_etilico'] ?? false),
            'observaciones' => $this->normalizarTextoRelacionado($persona['observaciones'] ?? null),
        ];
    }

    protected function personasRelacionadasPayload(Request $request): array
    {
        return collect($request->input('personas', []))
            ->filter(fn($persona) => is_array($persona) && !empty($persona['nombre']))
            ->map(fn($persona) => $this->personaRelacionadaPayload($persona))
            ->values()
            ->toArray();
    }

    protected function validarRelacionadosAdicionales(array $data): void
    {
        $placas = [];
        $series = [];
        $errores = [];

        foreach (($data['vehiculos_nuevos'] ?? []) as $index => $vehiculo) {
            $placa = $this->normalizarTokenRelacionado($vehiculo['placas'] ?? null);
            $serie = $this->normalizarTokenRelacionado($vehiculo['serie'] ?? null);

            if ($placa && empty($vehiculo['estado_placas'])) {
                $errores["vehiculos_nuevos.$index.estado_placas"][] = 'Selecciona el estado de las placas.';
            }

            if ($placa && in_array($placa, $placas, true)) {
                $errores["vehiculos_nuevos.$index.placas"][] = 'No repitas placas en los vehículos relacionados.';
            }

            if ($serie && in_array($serie, $series, true)) {
                $errores["vehiculos_nuevos.$index.serie"][] = 'No repitas números de serie en los vehículos relacionados.';
            }

            if ($placa) {
                $placas[] = $placa;
            }

            if ($serie) {
                $series[] = $serie;
            }
        }

        if (!empty($errores)) {
            throw ValidationException::withMessages($errores);
        }
    }

    public function create(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();
        $catalogos = $this->obtenerCatalogos();
        $destacamentos = $this->obtenerDestacamentos();
        $datosVista = $this->obtenerDatosVista();
        $user = $request->user();

        return response()->json([
            'ok' => true,
            'operativo' => $operativo,
            'catalogos' => $catalogos,
            'destacamentos' => $destacamentos,
            'dispositivosConfig' => $datosVista['dispositivosConfig'],
            'allCampos' => $datosVista['allCampos'],
            'defaults' => [
                'fecha' => now()->toDateString(),
                'hora' => now()->format('H:i'),
                'unidad_org_id' => $user->unidad_id ?? null,
                'delegacion_id' => $user->delegacion_id ?? null,
                'destacamento_id' => $user->destacamento_id ?? null,
                'user_id' => $user->id ?? null,
            ],
        ], 200);
    }

    public function index(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();

        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $query = OperativoDispositivo::with([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'fotos',
            'vehiculos',
            'personas',
        ])->where('operativo_id', $operativo->id);

        $userId = (int) optional($request->user())->id;
        $query->where(function ($q) use ($userId) {
            $q->aprobados();

            if ($userId > 0) {
                $q->orWhere('user_id', $userId);
            }
        });

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        if ($request->filled('catalogo_id')) {
            $query->where('operativo_dispositivo_catalogo_id', $request->query('catalogo_id'));
        }

        if ($request->filled('client_uuid')) {
            $query->where('client_uuid', $request->query('client_uuid'));
        }

        $result = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = array_map(function ($row) {
            return $this->transformDispositivo($row);
        }, $result->items());

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $operativo = $this->obtenerOperativoUnico();

            $this->inyectarDatosOrganizacion($request);

            $validator = Validator::make($request->all(), $this->reglasValidacion(false), $this->messages());

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $data = $validator->validated();

            $this->validarDatosOrganizacionFinal($data);
            $this->validarRelacionadosAdicionales($data);
            $this->limpiarCamposNoAplicables($data, (int) $data['operativo_dispositivo_catalogo_id']);

            if (!empty($data['client_uuid'])) {
                $existente = OperativoDispositivo::with([
                    'catalogo',
                    'operativo',
                    'unidad',
                    'delegacion',
                    'destacamento',
                    'usuario',
                    'fotos',
                    'vehiculos',
                    'personas',
                ])
                ->where('operativo_id', $operativo->id)
                ->where('client_uuid', $data['client_uuid'])
                ->first();

                if ($existente) {
                    return response()->json([
                        'message' => 'Dispositivo ya existente',
                        'created' => false,
                        'data' => $this->transformDispositivo($existente),
                        'meta' => [
                            'id' => $existente->id,
                            'client_uuid' => $existente->client_uuid,
                        ],
                    ], 200);
                }
            }

            $dispositivo = null;

            DB::transaction(function () use ($request, $operativo, $data, &$dispositivo) {
                $dispositivo = new OperativoDispositivo();
                $this->llenarDispositivo($dispositivo, $operativo, $data, $request);
                $dispositivo->save();

                if ($request->filled('vehiculos')) {
                    $dispositivo->vehiculos()->sync($request->vehiculos);
                }

                $this->crearVehiculosNuevos($dispositivo, $request);

                $personas = $this->personasRelacionadasPayload($request);

                if (!empty($personas)) {
                    $dispositivo->personas()->createMany($personas);
                }

                $this->guardarFotos($request, $dispositivo);
            });

            $dispositivo->load([
                'catalogo',
                'operativo',
                'unidad',
                'delegacion',
                'destacamento',
                'usuario',
                'fotos',
                'vehiculos',
                'personas',
            ]);

            app(GuardianesCaminoRevisionService::class)->notificarRevisionPendiente($dispositivo);

            return response()->json([
                'message' => 'Dispositivo capturado correctamente.',
                'created' => true,
                'data' => $this->transformDispositivo($dispositivo),
                'meta' => [
                    'id' => $dispositivo->id,
                    'client_uuid' => $dispositivo->client_uuid,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }
    }

    public function pendientesRevision(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();
        $revision = app(GuardianesCaminoRevisionService::class);
        $revision->assertPuedeRevisar($request->user());

        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $query = OperativoDispositivo::with([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'revisadoPor',
            'fotos',
            'vehiculos',
            'personas',
        ])->where('operativo_id', $operativo->id);

        $revision->aplicarScopePendientes($query, $request->user());

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        $result = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = array_map(function ($row) {
            return $this->transformDispositivo($row);
        }, $result->items());

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
        ], 200);
    }

    public function countPendientesRevision(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();
        $revision = app(GuardianesCaminoRevisionService::class);
        $revision->assertPuedeRevisar($request->user());

        $query = OperativoDispositivo::where('operativo_id', $operativo->id);
        $revision->aplicarScopePendientes($query, $request->user());

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        return response()->json([
            'total' => $query->count(),
        ], 200);
    }

    public function aprobarRevision(Request $request, $dispositivo)
    {
        $request->validate([
            'observacion_revision' => ['nullable', 'string'],
        ]);

        $operativo = $this->obtenerOperativoUnico();
        $dispositivo = OperativoDispositivo::with([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'revisadoPor',
            'fotos',
            'vehiculos',
            'personas',
        ])
        ->where('operativo_id', $operativo->id)
        ->find($dispositivo);

        if (!$dispositivo) {
            return response()->json([
                'message' => 'No encontrado.',
            ], 404);
        }

        app(GuardianesCaminoRevisionService::class)
            ->aprobar($dispositivo, $request->user(), $request->observacion_revision);

        $dispositivo->refresh()->load([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'revisadoPor',
            'fotos',
            'vehiculos',
            'personas',
        ]);

        return response()->json([
            'message' => 'Dispositivo aprobado correctamente.',
            'data' => $this->transformDispositivo($dispositivo),
        ], 200);
    }

    public function rechazarRevision(Request $request, $dispositivo)
    {
        $request->validate([
            'observacion_revision' => ['required', 'string'],
        ]);

        $operativo = $this->obtenerOperativoUnico();
        $dispositivo = OperativoDispositivo::with([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'revisadoPor',
            'fotos',
            'vehiculos',
            'personas',
        ])
        ->where('operativo_id', $operativo->id)
        ->find($dispositivo);

        if (!$dispositivo) {
            return response()->json([
                'message' => 'No encontrado.',
            ], 404);
        }

        app(GuardianesCaminoRevisionService::class)
            ->rechazar($dispositivo, $request->user(), $request->observacion_revision);

        $dispositivo->refresh()->load([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'revisadoPor',
            'fotos',
            'vehiculos',
            'personas',
        ]);

        return response()->json([
            'message' => 'Dispositivo rechazado correctamente.',
            'data' => $this->transformDispositivo($dispositivo),
        ], 200);
    }

    public function show($dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::with([
            'catalogo',
            'operativo',
            'unidad',
            'delegacion',
            'destacamento',
            'usuario',
            'fotos',
            'vehiculos',
            'personas',
        ])
        ->where('operativo_id', $operativo->id)
        ->find($dispositivo);

        if (!$dispositivo) {
            return response()->json([
                'message' => 'No encontrado.',
            ], 404);
        }

        if (!app(GuardianesCaminoRevisionService::class)->puedeVerDispositivo(Auth::user(), $dispositivo)) {
            return response()->json([
                'message' => 'Este dispositivo todavía está pendiente de revisión.',
            ], 403);
        }

        return response()->json([
            'data' => $this->transformDispositivo($dispositivo),
        ], 200);
    }

    public function update(Request $request, $dispositivo)
    {
        try {
            $operativo = $this->obtenerOperativoUnico();

            $dispositivo = OperativoDispositivo::with(['fotos','vehiculos','personas',])
                ->where('operativo_id', $operativo->id)
                ->find($dispositivo);

            if (!$dispositivo) {
                return response()->json([
                    'message' => 'No encontrado.',
                ], 404);
            }

            if (!app(GuardianesCaminoRevisionService::class)->puedeVerDispositivo($request->user(), $dispositivo)) {
                return response()->json([
                    'message' => 'Este dispositivo todavía está pendiente de revisión.',
                ], 403);
            }

            $this->inyectarDatosOrganizacion($request);

            $validator = Validator::make($request->all(), $this->reglasValidacion(true), $this->messages());

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $data = $validator->validated();

            if (!array_key_exists('operativo_dispositivo_catalogo_id', $data)) {
                $data['operativo_dispositivo_catalogo_id'] = $dispositivo->operativo_dispositivo_catalogo_id;
            }

            if (!array_key_exists('fecha', $data)) {
                $data['fecha'] = $dispositivo->fecha;
            }

            $this->validarDatosOrganizacionFinal(array_merge($dispositivo->toArray(), $data));
            $this->validarRelacionadosAdicionales($data);
            $this->limpiarCamposNoAplicables($data, (int) $data['operativo_dispositivo_catalogo_id']);

            DB::transaction(function () use ($request, $operativo, $data, $dispositivo) {
                $this->llenarDispositivo($dispositivo, $operativo, $data, $request);
                $dispositivo->save();

                if ($request->has('vehiculos')) {
                    $vehiculos = collect($request->input('vehiculos', []))
                        ->filter()
                        ->values()
                        ->all();

                    $dispositivo->vehiculos()->sync($vehiculos);
                }

                $this->crearVehiculosNuevos($dispositivo, $request);

                if ($request->has('personas')) {
                    $dispositivo->personas()->delete();

                    $personas = $this->personasRelacionadasPayload($request);

                    if (!empty($personas)) {
                        $dispositivo->personas()->createMany($personas);
                    }
                }

                $this->guardarFotos($request, $dispositivo);
            });

            $dispositivo->load([
                'catalogo',
                'operativo',
                'unidad',
                'delegacion',
                'destacamento',
                'usuario',
                'fotos',
                'vehiculos',
                'personas',
            ]);

            return response()->json([
                'message' => 'Dispositivo actualizado correctamente.',
                'data' => $this->transformDispositivo($dispositivo),
            ], 200);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        }
    }

    public function destroy(Request $request, $dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::with('fotos')
            ->where('operativo_id', $operativo->id)
            ->find($dispositivo);

        if (!$dispositivo) {
            return response()->json([
                'message' => 'No encontrado.',
            ], 404);
        }

        if (!app(GuardianesCaminoRevisionService::class)->puedeVerDispositivo($request->user(), $dispositivo)) {
            return response()->json([
                'message' => 'Este dispositivo todavía está pendiente de revisión.',
            ], 403);
        }

        DB::transaction(function () use ($dispositivo) {
            foreach ($dispositivo->fotos as $foto) {
                if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
                    Storage::disk('public')->delete($foto->ruta);
                }
                $foto->delete();
            }

            $dispositivo->delete();
        });

        return response()->json([
            'message' => 'Dispositivo eliminado correctamente.',
        ], 200);
    }

    public function whatsapp($dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::with([
            'catalogo',
            'destacamento',
            'fotos',
            'vehiculos',
            'personas',
        ])
            ->where('operativo_id', $operativo->id)
            ->find($dispositivo);

        if (!$dispositivo) {
            return response()->json([
                'message' => 'No encontrado.',
            ], 404);
        }

        if (!app(GuardianesCaminoRevisionService::class)->puedeVerDispositivo(Auth::user(), $dispositivo)) {
            return response()->json([
                'message' => 'Este dispositivo todavía está pendiente de revisión.',
            ], 403);
        }

        $tipo = strtoupper($dispositivo->catalogo->nombre ?? '');

        $header = "GUARDIA CIVIL MICHOACÁN\n"
            . "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n"
            . "UNIDAD DE PROTECCIÓN EN CARRETERAS\n\n"
            . "\"GUARDIANES DEL CAMINO\"\n\n"
            . "DESTACAMENTO " . strtoupper($dispositivo->destacamento_nombre_snapshot ?? 'SIN DESTACAMENTO') . "\n\n";

        $fecha = optional($dispositivo->fecha)->format('d/m/Y');
        $hora = $dispositivo->hora ?? '';

        $texto = $header;

        if (
            Str::contains($tipo, 'CABALLERO')
            || Str::contains($tipo, 'ACOMPAÑAMIENTOS')
            || Str::contains($tipo, 'ABANDERAMIENTOS')
            || Str::contains($tipo, 'AUXILIOS VIALES')
            || filled($dispositivo->nombre_conductor)
        ) {
            $texto .= "ASUNTO: APOYO A USUARIO\n\n";
            $texto .= "FECHA: {$fecha}\n\n";

            if ($dispositivo->hora_inicio) {
                $texto .= "Qtr Inicio {$dispositivo->hora_inicio} horas\n\n";
            }

            if ($dispositivo->hora_fin) {
                $texto .= "Qtr Final {$dispositivo->hora_fin} horas\n\n";
            }

            if ($dispositivo->carretera || $dispositivo->kilometro) {
                $texto .= "Ubicación {$dispositivo->carretera} km {$dispositivo->kilometro}\n";
            }

            if ($dispositivo->tramo) {
                $texto .= "Tramo: {$dispositivo->tramo}\n\n";
            }

            if ($dispositivo->lat && $dispositivo->lng) {
                $texto .= "Georreferencia\nLat {$dispositivo->lat} Lng {$dispositivo->lng}\n\n";
            }

            if (Str::contains($tipo, 'ACOMPAÑAMIENTOS') && !empty($dispositivo->tipo_acompanamiento)) {
                $texto .= "TIPO DE ACOMPAÑAMIENTO: {$dispositivo->tipo_acompanamiento}\n\n";
            }

            if (Str::contains($tipo, 'ABANDERAMIENTOS') && !empty($dispositivo->tipo_abanderamiento)) {
                $texto .= "TIPO DE ABANDERAMIENTO: {$dispositivo->tipo_abanderamiento}\n\n";
            }

            if (Str::contains($tipo, 'AUXILIOS VIALES') && !empty($dispositivo->tipo_auxilio_vial)) {
                $texto .= "TIPO DE AUXILIO VIAL: {$dispositivo->tipo_auxilio_vial}\n\n";
            }

            if ($dispositivo->vehiculos->isNotEmpty()) {
                $texto .= "VEHÍCULOS RELACIONADOS\n";

                foreach ($dispositivo->vehiculos as $vehiculo) {
                    $descripcionVehiculo = trim(collect([
                        $vehiculo->marca,
                        $vehiculo->linea,
                        $vehiculo->tipo,
                        $vehiculo->color,
                    ])->filter()->implode(' '));

                    $placas = $vehiculo->placas ?: 'SIN PLACAS';

                    $texto .= "- {$descripcionVehiculo} | PLACAS: {$placas}\n";
                }

                $texto .= "\n";
            }

            if ($dispositivo->personas->isNotEmpty()) {
                $texto .= "PERSONAS RELACIONADAS\n";

                foreach ($dispositivo->personas as $persona) {
                    $nombre = $persona->nombre ?: 'SIN NOMBRE';
                    $tipoParticipacion = $persona->tipo_participacion ?: 'SIN TIPO';

                    $texto .= "- {$nombre} | {$tipoParticipacion}\n";
                }

                $texto .= "\n";
            }

            $texto .= ($dispositivo->narrativa ?? '') . "\n\n";
            $texto .= "LA GUARDIA CIVIL NO TE MULTA, TE CUIDA EN EL CAMINO.\n\n";
            $texto .= "RESPETUOSAMENTE";
        } else {
            $texto .= "ASUNTO: PATRULLAJE DE SEGURIDAD Y VIGILANCIA\n\n";
            $texto .= "Fecha: {$fecha}\n";
            $texto .= "Hora: {$hora} HRS.\n\n";

            if ($dispositivo->lat && $dispositivo->lng) {
                $texto .= "COORDENADAS\n{$dispositivo->lat},{$dispositivo->lng}\n\n";
            }

            if ($dispositivo->vehiculos->isNotEmpty()) {
                $texto .= "VEHÍCULOS RELACIONADOS\n";

                foreach ($dispositivo->vehiculos as $vehiculo) {
                    $descripcionVehiculo = trim(collect([
                        $vehiculo->marca,
                        $vehiculo->linea,
                        $vehiculo->tipo,
                        $vehiculo->color,
                    ])->filter()->implode(' '));

                    $placas = $vehiculo->placas ?: 'SIN PLACAS';

                    $texto .= "- {$descripcionVehiculo} | PLACAS: {$placas}\n";
                }

                $texto .= "\n";
            }

            if ($dispositivo->personas->isNotEmpty()) {
                $texto .= "PERSONAS RELACIONADAS\n";

                foreach ($dispositivo->personas as $persona) {
                    $nombre = $persona->nombre ?: 'SIN NOMBRE';
                    $tipoParticipacion = $persona->tipo_participacion ?: 'SIN TIPO';

                    $texto .= "- {$nombre} | {$tipoParticipacion}\n";
                }

                $texto .= "\n";
            }

            $texto .= ($dispositivo->narrativa ?? '') . "\n\n";
            $texto .= "AHORA LA GUARDIA CIVIL NO TE MULTA, TE CUIDA EN EL CAMINO\n\n";

            if ($dispositivo->estado_fuerza_participante || $dispositivo->crps_participantes) {
                $texto .= "ESTADO DE FUERZA\n";
                $texto .= "CRPS ({$dispositivo->crps_participantes})\n";
                $texto .= "ELEMENTOS ({$dispositivo->estado_fuerza_participante})\n\n";
            }

            $texto .= "RESPETUOSAMENTE";
        }

        return response()->json([
            'ok' => true,
            'text' => $texto,
            'wa_url' => 'https://wa.me/?text=' . urlencode($texto),
            'fotos' => $this->fotoUrls($dispositivo),
        ], 200);
    }

    private function transformDispositivo(OperativoDispositivo $dispositivo): array
    {
        $data = $dispositivo->toArray();

        $data['fotos'] = collect($dispositivo->fotos ?? [])->map(function ($foto) {
            $row = $foto->toArray();
            $row['url'] = $this->publicStoragePath($foto->ruta);
            return $row;
        })->values()->all();

        return $data;
    }

    private function fotoUrls(OperativoDispositivo $dispositivo): array
    {
        return collect($dispositivo->fotos ?? [])->map(function ($foto) {
            return $this->publicStoragePath($foto->ruta);
        })->filter()->values()->all();
    }

    private function validationErrorResponse(array $errors)
    {
        $first = null;

        foreach ($errors as $field => $msgs) {
            if (!empty($msgs[0])) {
                $first = $msgs[0];
                break;
            }
        }

        return response()->json([
            'message' => $first ?: 'Revisa los campos marcados e inténtalo de nuevo.',
            'errors' => $errors,
        ], 422);
    }

    private function publicStoragePath(?string $storedPath): ?string
    {
        if (empty($storedPath)) {
            return null;
        }

        $u = Storage::disk('public')->url($storedPath);
        $p = parse_url($u);

        if (is_array($p) && !empty($p['path'])) {
            $out = $p['path'];
            if (!empty($p['query'])) {
                $out .= '?' . $p['query'];
            }
            return $out;
        }

        return $u;
    }

    private function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string' => 'Escribe un texto válido.',
            'max' => 'Máximo :max caracteres.',
            'min' => 'El valor mínimo es :min.',
            'date' => 'Escribe una fecha válida.',
            'date_format' => 'La hora debe tener formato HH:MM.',
            'integer' => 'Solo se permiten números enteros.',
            'numeric' => 'Solo se permiten números.',
            'boolean' => 'Valor inválido.',
            'image' => 'El archivo debe ser una imagen.',
            'mimes' => 'Formato no permitido. Usa: :values.',
            'array' => 'Formato inválido.',
            'exists' => 'El valor seleccionado no existe.',
            'between' => 'El valor está fuera de rango.',
            'regex' => 'Formato inválido.',
            'digits' => 'Debe tener :digits dígitos.',
            'in' => 'El valor seleccionado no es válido.',

            'operativo_dispositivo_catalogo_id.required' => 'Debes seleccionar un tipo de dispositivo.',
            'fecha.required' => 'Debes capturar la fecha.',
            'destacamento_id.exists' => 'El destacamento seleccionado no existe.',
            'fotos.*.max' => 'Cada foto debe pesar máximo 5 MB.',
        ];
    }
}
