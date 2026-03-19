<?php

namespace App\Http\Controllers;

use App\Models\Destacamento;
use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\OperativoDispositivo;
use App\Models\OperativoDispositivoCatalogo;
use App\Models\OperativoDispositivoFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuardianesCaminoDispositivoController extends Controller
{
    protected function obtenerOperativoUnico()
    {
        $catalogo = OperativoCatalogo::where('slug', 'guardianes-del-camino')->firstOrFail();

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

    protected function reglasValidacion(): array
    {
        return [
            'operativo_dispositivo_catalogo_id' => ['required', 'integer', 'exists:operativo_dispositivo_catalogos,id'],
            'client_uuid' => ['nullable', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
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
        ];
    }

    protected function inyectarDatosOrganizacion(Request $request): void
    {
        $user = Auth::user();

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
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);

        return $valor !== false ? $valor : mb_strtoupper(trim((string) $valor), 'UTF-8');
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

        $config = config('guardianes_camino.dispositivos', []);
        $nombreNormalizado = $this->normalizarNombre($catalogo->nombre);

        foreach ($config as $clave => $item) {
            if ($this->normalizarNombre($clave) === $nombreNormalizado) {
                return $item['campos'] ?? [];
            }
        }

        return [];
    }

    protected function limpiarCamposNoAplicables(array &$data, int $catalogoId): void
    {
        $allCampos = config('guardianes_camino.all_campos', []);
        $camposActivos = $this->obtenerCamposConfigPorCatalogo($catalogoId);
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
                $data[$campo] = is_string($data[$campo] ?? null) ? null : 0;
            }
        }
    }

    protected function llenarDispositivo(OperativoDispositivo $dispositivo, Operativo $operativo, array $data): void
    {
        $destacamento = !empty($data['destacamento_id'])
            ? Destacamento::find($data['destacamento_id'])
            : null;

        $dispositivo->client_uuid = $data['client_uuid'] ?? $dispositivo->client_uuid ?? (string) Str::uuid();
        $dispositivo->sync_status = 'synced';
        $dispositivo->sync_error = null;
        $dispositivo->synced_at = now();

        $dispositivo->operativo_id = $operativo->id;
        $dispositivo->operativo_dispositivo_catalogo_id = $data['operativo_dispositivo_catalogo_id'];

        $dispositivo->tipo_reporte = $data['tipo_reporte'] ?? null;
        $dispositivo->asunto = $data['asunto'] ?? null;

        $dispositivo->fecha = $data['fecha'];
        $dispositivo->hora = $data['hora'] ?? null;
        $dispositivo->hora_inicio = $data['hora_inicio'] ?? null;
        $dispositivo->hora_fin = $data['hora_fin'] ?? null;

        $dispositivo->unidad_org_id = $data['unidad_org_id'];
        $dispositivo->delegacion_id = $data['delegacion_id'] ?? null;
        $dispositivo->destacamento_id = $data['destacamento_id'] ?? null;
        $dispositivo->user_id = $dispositivo->exists ? $dispositivo->user_id : Auth::id();

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

        $dispositivo->cantidad = $data['cantidad'] ?? 0;
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
            $dispositivo->created_by = Auth::id();
        }

        $dispositivo->updated_by = Auth::id();
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
            $foto->created_by = Auth::id();
            $foto->save();
        }
    }

    public function create()
    {
        $operativo = $this->obtenerOperativoUnico();
        $catalogos = $this->obtenerCatalogos();
        $destacamentos = $this->obtenerDestacamentos();
        $datosVista = $this->obtenerDatosVista();

        return view('guardianes_camino.dispositivos.create', array_merge([
            'operativo' => $operativo,
            'catalogos' => $catalogos,
            'destacamentos' => $destacamentos,
        ], $datosVista));
    }

    public function store(Request $request)
    {
        $operativo = $this->obtenerOperativoUnico();

        $this->inyectarDatosOrganizacion($request);

        $data = $request->validate($this->reglasValidacion());

        $this->validarDatosOrganizacionFinal($data);

        $this->limpiarCamposNoAplicables($data, (int) $data['operativo_dispositivo_catalogo_id']);

        $dispositivo = new OperativoDispositivo();
        $this->llenarDispositivo($dispositivo, $operativo, $data);
        $dispositivo->save();

        $this->guardarFotos($request, $dispositivo);

        return redirect()
            ->route('guardianes_camino.dispositivos.show', $dispositivo->id)
            ->with('success', 'Dispositivo capturado correctamente.');
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
            ])
            ->where('operativo_id', $operativo->id)
            ->findOrFail($dispositivo);

        return view('guardianes_camino.dispositivos.show', compact('operativo', 'dispositivo'));
    }

    public function edit($dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::with('fotos')
            ->where('operativo_id', $operativo->id)
            ->findOrFail($dispositivo);

        $catalogos = $this->obtenerCatalogos();
        $destacamentos = $this->obtenerDestacamentos();
        $datosVista = $this->obtenerDatosVista();

        return view('guardianes_camino.dispositivos.edit', array_merge([
            'operativo' => $operativo,
            'dispositivo' => $dispositivo,
            'catalogos' => $catalogos,
            'destacamentos' => $destacamentos,
        ], $datosVista));
    }

    public function update(Request $request, $dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::where('operativo_id', $operativo->id)->findOrFail($dispositivo);

        $this->inyectarDatosOrganizacion($request);

        $data = $request->validate($this->reglasValidacion());

        $this->validarDatosOrganizacionFinal($data);

        $this->limpiarCamposNoAplicables($data, (int) $data['operativo_dispositivo_catalogo_id']);

        $this->llenarDispositivo($dispositivo, $operativo, $data);
        $dispositivo->save();

        $this->guardarFotos($request, $dispositivo);

        return redirect()
            ->route('guardianes_camino.dispositivos.show', $dispositivo->id)
            ->with('success', 'Dispositivo actualizado correctamente.');
    }

    public function destroy($dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::with('fotos')
            ->where('operativo_id', $operativo->id)
            ->findOrFail($dispositivo);

        foreach ($dispositivo->fotos as $foto) {
            if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
                Storage::disk('public')->delete($foto->ruta);
            }
            $foto->delete();
        }

        $dispositivo->delete();

        return redirect()
            ->route('guardianes_camino.index')
            ->with('success', 'Dispositivo eliminado correctamente.');
    }

    public function whatsapp($dispositivo)
    {
        $operativo = $this->obtenerOperativoUnico();

        $dispositivo = OperativoDispositivo::with(['catalogo', 'destacamento'])
            ->where('operativo_id', $operativo->id)
            ->findOrFail($dispositivo);

        $tipo = strtoupper($dispositivo->catalogo->nombre ?? '');

        $header = "GUARDIA CIVIL MICHOACÁN\n"
            . "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n"
            . "UNIDAD DE PROTECCIÓN EN CARRETERAS\n\n"
            . "\"GUARDIANES DEL CAMINO\"\n\n"
            . "DESTACAMENTO " . strtoupper($dispositivo->destacamento_nombre_snapshot ?? 'SIN DESTACAMENTO') . "\n\n";

        $fecha = optional($dispositivo->fecha)->format('d/m/Y');
        $hora = $dispositivo->hora ?? '';

        $texto = $header;

        if (str_contains($tipo, 'CABALLEROS') || filled($dispositivo->nombre_conductor)) {
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
            'text' => $texto
        ]);
    }
}
