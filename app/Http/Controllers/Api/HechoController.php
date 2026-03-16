<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

use App\Services\WhatsApp\WhatsAppBot;
use App\Services\WhatsApp\WhatsAppLink;
use App\Models\Dictamen;
use Illuminate\Support\Facades\DB;

class HechoController extends Controller
{
    private const SECTORES = ['REVOLUCION','NUEVA ESPANA','INDEPENDENCIA','REPUBLICA','CENTRO'];
    private const TIEMPOS  = ['DIA','NOCHE','AMANECER','ATARDECER'];
    private const CLIMAS   = ['BUENO','MALO','NUBLADO','LLUVIOSO'];
    private const COND     = ['BUENO','REGULAR','MALO'];
    private const SITUAS   = ['RESUELTO','PENDIENTE','TURNADO','REPORTE'];

    public function index(Request $request)
    {
        $perPage = (int)($request->query('per_page', 20));
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $user = $request->user();

        $query = Hechos::with(['vehiculos.conductores', 'lesionados']);

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        $this->applyHechosVisibilityScope($query, $user);

        $hechos = $query->orderByDesc('id')->paginate($perPage);

        return response()->json($hechos);
    }

    public function buscar(Request $request)
    {
        $user = $request->user();

        $q = trim((string)$request->query('q', ''));
        $perPage = (int)($request->query('per_page', 20));
        $perPage = $perPage > 0 ? min($perPage, 50) : 20;

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
            ], 200);
        }

        $qNorm = strtoupper($this->removeAccents($q));
        $like  = '%' . addcslashes($qNorm, "%_\\") . '%';

        $query = Hechos::query()
            ->select([
                'hechos.id',
                'hechos.perito',
                'hechos.folio_c5i',
                'hechos.fecha',
                'hechos.calle',
                'hechos.colonia',
                'hechos.municipio',
                'hechos.situacion',
                'hechos.foto_lugar',
                'hechos.foto_situacion',
                'hechos.danos_patrimoniales',
                'hechos.propiedades_afectadas',
                'hechos.monto_danos_patrimoniales',
            ])
            ->with([
                'vehiculos' => function ($v) {
                    $v->select(['vehiculos.id', 'vehiculos.placas', 'vehiculos.serie'])
                      ->with([
                          'conductores' => function ($c) {
                              $c->select(['conductores.id', 'conductores.nombre']);
                          }
                      ]);
                },
                'lesionados' => function ($l) {
                    $l->select(['lesionados.id', 'lesionados.hecho_id', 'lesionados.nombre']);
                },
            ]);

        $this->applyHechosVisibilityScope($query, $user);

        $query->where(function ($w) use ($q, $like) {
                if (ctype_digit($q)) {
                    $w->orWhere('hechos.id', (int)$q);
                }

                $w->orWhere('hechos.folio_c5i', 'like', $like)
                  ->orWhere('hechos.calle', 'like', $like)
                  ->orWhere('hechos.colonia', 'like', $like);

                $w->orWhereHas('vehiculos', function ($v) use ($like) {
                    $v->where(function ($vv) use ($like) {
                        $vv->where('vehiculos.placas', 'like', $like)
                           ->orWhere('vehiculos.serie', 'like', $like);
                    });
                });

                $w->orWhereHas('vehiculos.conductores', function ($c) use ($like) {
                    $c->where('conductores.nombre', 'like', $like);
                });

                $w->orWhereHas('lesionados', function ($l) use ($like) {
                    $l->where('lesionados.nombre', 'like', $like);
                });
            })
            ->orderByDesc('hechos.id');

        $results = $query->paginate($perPage);

        $data = array_map(function ($row) {
            $row['foto_lugar_url']     = $this->publicStoragePath($row['foto_lugar'] ?? null);
            $row['foto_situacion_url'] = $this->publicStoragePath($row['foto_situacion'] ?? null);
            return $row;
        }, $results->items());

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
                'last_page'    => $results->lastPage(),
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $this->normalizeCatalogFields($request);

        $rules = [
            'client_uuid' => 'nullable|string|max:36',

            'folio_c5i'             => 'required|string|max:20',
            'perito'                => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad'                => 'required|string|max:50',
            'hora'                  => 'required|date_format:H:i',
            'fecha'                 => 'required|date',
            'sector'                => ['required','string', Rule::in(self::SECTORES)],
            'calle'                 => 'required|string|max:255',
            'colonia'               => 'required|string|max:255',
            'entre_calles'          => 'nullable|string|max:255',
            'municipio'             => 'required|string|max:100',
            'tipo_hecho'            => 'required|string|max:255',
            'superficie_via'        => 'required|string|max:50',
            'tiempo'                => ['required','string', Rule::in(self::TIEMPOS)],
            'clima'                 => ['required','string', Rule::in(self::CLIMAS)],
            'condiciones'           => ['required','string', Rule::in(self::COND)],
            'control_transito'      => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas'                => 'required|string|max:255',
            'colision_camino'       => 'required|string|max:255',
            'situacion'             => ['required','string', Rule::in(self::SITUAS)],
            'oficio_mp'             => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp'          => 'nullable|integer|min:0|required_if:situacion,TURNADO',
            'personas_mp'           => 'nullable|integer|min:0|required_if:situacion,TURNADO',
            'dictamen_id'           => 'nullable|required_if:situacion,TURNADO|exists:dictamens,id',
            'danos_patrimoniales'   => 'nullable|boolean',
            'propiedades_afectadas' => 'nullable|string|max:2000',
            'monto_danos_patrimoniales' => 'nullable|numeric|min:0',
            'lat'                   => 'required|numeric|between:-90,90',
            'lng'                   => 'required|numeric|between:-180,180',
            'calidad_geo'           => 'nullable|string|max:20',
            'nota_geo'              => 'nullable|string|max:1000',
            'fuente_ubicacion'      => 'nullable|string|max:20',
            'ubicacion_formateada'  => 'nullable|string|max:2000',
            'place_id'              => 'nullable|string|max:128',
            'coords_pair'           => 'nullable',
            'foto_lugar'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        $messages = $this->messages();

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($v) use ($request) {
            $situacion = strtoupper($this->removeAccents((string)$request->input('situacion', '')));

            if ($situacion === 'RESUELTO' && !$request->hasFile('foto_situacion')) {
                $v->errors()->add('foto_situacion', 'Para marcar el hecho como RESUELTO debes subir la foto de situación.');
            }

            $hasLat = $request->filled('lat');
            $hasLng = $request->filled('lng');

            if ($hasLat xor $hasLng) {
                $v->errors()->add('lat', 'Si envías ubicación, debes enviar lat y lng.');
                $v->errors()->add('lng', 'Si envías ubicación, debes enviar lat y lng.');
            }
        });

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if (!empty($validated['client_uuid'])) {
            $hechoExistente = Hechos::query()->where('client_uuid', $validated['client_uuid'])->first();

            if ($hechoExistente) {
                $hechoExistente->load(['vehiculos.conductores', 'lesionados']);

                return response()->json([
                    'message' => 'Hecho ya existente',
                    'created' => false,
                    'data' => $this->withFotoUrls($hechoExistente),
                    'meta' => [
                        'id' => $hechoExistente->id,
                        'client_uuid' => $hechoExistente->client_uuid,
                    ],
                ], 200);
            }
        }

        $dictamenId = $validated['dictamen_id'] ?? null;
        unset($validated['dictamen_id']);

        $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');
        $validated['danos_patrimoniales'] = $request->boolean('danos_patrimoniales');

        foreach ($validated as $key => $value) {
            if (is_string($value) && $key !== 'client_uuid') {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $hasCoords = $request->filled('lat') && $request->filled('lng');
        if ($hasCoords && empty($validated['fuente_ubicacion'])) {
            $validated['fuente_ubicacion'] = 'GPS_APP';
        }

        if (!$request->filled('lat')) {
            $validated['lat'] = null;
        }

        if (!$request->filled('lng')) {
            $validated['lng'] = null;
        }

        if (!$request->has('danos_patrimoniales')) {
            $validated['danos_patrimoniales'] = 0;
        }

        if ($request->has('monto_danos_patrimoniales') && !$request->filled('monto_danos_patrimoniales')) {
            $validated['monto_danos_patrimoniales'] = null;
        }

        if ($request->has('propiedades_afectadas') && trim((string)$request->input('propiedades_afectadas')) === '') {
            $validated['propiedades_afectadas'] = null;
        }

        $validated['created_by'] = $user->id;

        $unidadOrg = (int)($user->unidad_id ?? 0);
        if ($unidadOrg <= 0) {
            $unidadOrg = 1;
        }
        $validated['unidad_org_id'] = $unidadOrg;

        $delegacionId = (int)($user->delegacion_id ?? 0);
        $validated['delegacion_id'] = $delegacionId > 0 ? $delegacionId : null;

        try {
            $hecho = null;

            DB::transaction(function () use ($request, $validated, $dictamenId, &$hecho) {
                $hecho = Hechos::create($validated);

                $updates = [];

                if ($request->hasFile('foto_lugar')) {
                    $updates['foto_lugar'] = $request->file('foto_lugar')->store("hechos/{$hecho->id}", 'public');
                }

                if ($request->hasFile('foto_situacion')) {
                    $updates['foto_situacion'] = $request->file('foto_situacion')->store("hechos/{$hecho->id}", 'public');
                }

                if (!empty($updates)) {
                    $hecho->update($updates);
                }

                $situacion = strtoupper((string)($validated['situacion'] ?? ''));

                if ($situacion === 'TURNADO' && $dictamenId) {
                    $dictamen = Dictamen::query()->lockForUpdate()->findOrFail($dictamenId);

                    if (!empty($dictamen->hecho_id) && (int)$dictamen->hecho_id !== (int)$hecho->id) {
                        throw new \RuntimeException('Ese dictamen ya está ligado a otro hecho.');
                    }

                    $dictamen->hecho_id = $hecho->id;
                    $dictamen->save();
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'dictamen_id' => [$e->getMessage()],
                ],
            ], 422);
        }

        $hecho->load(['vehiculos.conductores', 'lesionados']);

        return response()->json([
            'message' => 'Hecho creado exitosamente',
            'created' => true,
            'data' => $this->withFotoUrls($hecho),
            'meta' => [
                'id' => $hecho->id,
                'client_uuid' => $hecho->client_uuid,
            ],
        ], 201);
    }

    public function show(Hechos $hecho)
    {
        $user = request()->user();

        $q = Hechos::query()->whereKey($hecho->id);
        $this->applyHechosVisibilityScope($q, $user);

        if (!$q->exists()) {
            return response()->json([
                'message' => 'No encontrado.',
            ], 404);
        }

        $hecho->load(['vehiculos.conductores', 'lesionados']);

        return response()->json([
            'data' => $this->withFotoUrls($hecho),
        ], 200);
    }

    public function update(Request $request, Hechos $hecho)
{
    $user = $request->user();

    $this->normalizeCatalogFields($request);

    $rules = [
        'folio_c5i' => [
            'sometimes', 'required', 'string', 'max:20',
            Rule::unique('hechos', 'folio_c5i')->ignore($hecho->id),
        ],
        'perito'                => 'sometimes|required|string|max:255',
        'autorizacion_practico' => 'sometimes|nullable|string|max:255',
        'unidad'                => 'sometimes|required|string|max:50',
        'hora'                  => 'sometimes|required|date_format:H:i',
        'fecha'                 => 'sometimes|required|date',
        'sector'                => ['sometimes','required','string', Rule::in(self::SECTORES)],
        'calle'                 => 'sometimes|required|string|max:255',
        'colonia'               => 'sometimes|required|string|max:255',
        'entre_calles'          => 'sometimes|nullable|string|max:255',
        'municipio'             => 'sometimes|required|string|max:100',
        'tipo_hecho'            => 'sometimes|required|string|max:255',
        'superficie_via'        => 'sometimes|required|string|max:50',
        'tiempo'                => ['sometimes','required','string', Rule::in(self::TIEMPOS)],
        'clima'                 => ['sometimes','required','string', Rule::in(self::CLIMAS)],
        'condiciones'           => ['sometimes','required','string', Rule::in(self::COND)],
        'control_transito'      => 'sometimes|required|string|max:50',
        'checaron_antecedentes' => 'sometimes|nullable|boolean',
        'causas'                => 'sometimes|required|string|max:255',
        'colision_camino'       => 'sometimes|required|string|max:255',
        'situacion'             => ['sometimes','required','string', Rule::in(self::SITUAS)],
        'oficio_mp'             => 'sometimes|nullable|string|max:255|required_if:situacion,TURNADO',
        'vehiculos_mp'          => 'sometimes|nullable|integer|min:0|required_if:situacion,TURNADO',
        'personas_mp'           => 'sometimes|nullable|integer|min:0|required_if:situacion,TURNADO',

        'dictamen_id'           => 'sometimes|nullable|required_if:situacion,TURNADO|exists:dictamens,id',

        'danos_patrimoniales'        => 'sometimes|nullable|boolean',
        'propiedades_afectadas'      => 'sometimes|nullable|string|max:2000',
        'monto_danos_patrimoniales'  => 'sometimes|nullable|numeric|min:0',

        'lat'                   => 'sometimes|required|numeric|between:-90,90',
        'lng'                   => 'sometimes|required|numeric|between:-180,180',
        'calidad_geo'           => 'sometimes|nullable|string|max:20',
        'nota_geo'              => 'sometimes|nullable|string|max:1000',
        'fuente_ubicacion'      => 'sometimes|nullable|string|max:20',
        'ubicacion_formateada'  => 'sometimes|nullable|string|max:2000',
        'place_id'              => 'sometimes|nullable|string|max:128',

        'foto_lugar'            => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'foto_situacion'        => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
    ];

    $messages = $this->messages();

    $validator = Validator::make($request->all(), $rules, $messages);

    $validator->after(function ($v) use ($request, $hecho) {
        $situacionNueva = $request->has('situacion')
            ? strtoupper($this->removeAccents((string)$request->input('situacion')))
            : null;

        $situacionEfectiva = $situacionNueva ?? strtoupper((string)($hecho->situacion ?? ''));

        if ($situacionEfectiva === 'RESUELTO') {
            $yaTieneFoto  = !empty($hecho->foto_situacion);
            $vieneArchivo = $request->hasFile('foto_situacion');

            if (!$yaTieneFoto && !$vieneArchivo) {
                $v->errors()->add('foto_situacion', 'Para marcar el hecho como RESUELTO debes subir la foto de situación.');
            }
        }

        $hasLat = $request->filled('lat');
        $hasLng = $request->filled('lng');
        if ($hasLat xor $hasLng) {
            $v->errors()->add('lat', 'Si envías ubicación, debes enviar lat y lng.');
            $v->errors()->add('lng', 'Si envías ubicación, debes enviar lat y lng.');
        }
    });

    if ($validator->fails()) {
        return $this->validationErrorResponse($validator->errors()->toArray());
    }

    $validated = $validator->validated();

    $dictamenId = $validated['dictamen_id'] ?? null;
    $dictamenIdProvided = array_key_exists('dictamen_id', $validated);
    unset($validated['dictamen_id']);

    if ($request->has('checaron_antecedentes')) {
        $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');
    }

    if ($request->has('danos_patrimoniales')) {
        $validated['danos_patrimoniales'] = $request->boolean('danos_patrimoniales');
    }

    foreach ($validated as $key => $value) {
        if (is_string($value)) {
            $validated[$key] = strtoupper($this->removeAccents($value));
        }
    }

    $hasCoords = $request->filled('lat') && $request->filled('lng');
    if ($hasCoords && empty($validated['fuente_ubicacion'])) {
        $validated['fuente_ubicacion'] = 'GPS_APP';
    }

    if ($request->has('lat') && !$request->filled('lat')) $validated['lat'] = null;
    if ($request->has('lng') && !$request->filled('lng')) $validated['lng'] = null;

    if ($request->has('monto_danos_patrimoniales') && !$request->filled('monto_danos_patrimoniales')) {
        $validated['monto_danos_patrimoniales'] = null;
    }
    if ($request->has('propiedades_afectadas') && trim((string)$request->input('propiedades_afectadas')) === '') {
        $validated['propiedades_afectadas'] = null;
    }

    $validated['updated_by'] = $user->id;

    unset($validated['unidad_org_id']);

    $newFotoLugarPath = null;
    $newFotoSituacionPath = null;
    $oldFotoLugar = $hecho->foto_lugar;
    $oldFotoSituacion = $hecho->foto_situacion;

    try {
        DB::transaction(function () use (
            $request,
            $hecho,
            $validated,
            $dictamenId,
            $dictamenIdProvided,
            &$newFotoLugarPath,
            &$newFotoSituacionPath
        ) {
            if ($request->hasFile('foto_lugar')) {
                $newFotoLugarPath = $request->file('foto_lugar')->store("hechos/{$hecho->id}", 'public');
                $validated['foto_lugar'] = $newFotoLugarPath;
            }

            if ($request->hasFile('foto_situacion')) {
                $newFotoSituacionPath = $request->file('foto_situacion')->store("hechos/{$hecho->id}", 'public');
                $validated['foto_situacion'] = $newFotoSituacionPath;
            }

            $hecho->update($validated);

            $situacion = $request->has('situacion')
                ? strtoupper($this->removeAccents((string)$request->input('situacion')))
                : strtoupper((string)($hecho->situacion ?? ''));

            $dictamenActual = $hecho->dictamen;

            if ($situacion === 'TURNADO') {

                if ($dictamenIdProvided) {

                    if ($dictamenActual && (string)$dictamenActual->id !== (string)$dictamenId) {
                        $dictamenActual = Dictamen::query()->lockForUpdate()->find($dictamenActual->id);
                        if ($dictamenActual) {
                            $dictamenActual->hecho_id = null;
                            $dictamenActual->save();
                        }
                    }

                    if ($dictamenId) {
                        $nuevo = Dictamen::query()->lockForUpdate()->findOrFail($dictamenId);

                        if (!empty($nuevo->hecho_id) && (int)$nuevo->hecho_id !== (int)$hecho->id) {
                            throw new \RuntimeException('Ese dictamen ya está ligado a otro hecho.');
                        }

                        $nuevo->hecho_id = $hecho->id;
                        $nuevo->save();
                    }

                }

            } else {

                if ($dictamenActual) {
                    $dictamenActual = Dictamen::query()->lockForUpdate()->find($dictamenActual->id);
                    if ($dictamenActual) {
                        $dictamenActual->hecho_id = null;
                        $dictamenActual->save();
                    }
                }
            }
        });
    } catch (\Throwable $e) {
        if ($newFotoLugarPath && Storage::disk('public')->exists($newFotoLugarPath)) {
            Storage::disk('public')->delete($newFotoLugarPath);
        }
        if ($newFotoSituacionPath && Storage::disk('public')->exists($newFotoSituacionPath)) {
            Storage::disk('public')->delete($newFotoSituacionPath);
        }

        return response()->json([
            'message' => $e->getMessage(),
            'errors' => [
                'dictamen_id' => [$e->getMessage()],
            ],
        ], 422);
    }

    if ($newFotoLugarPath && !empty($oldFotoLugar) && Storage::disk('public')->exists($oldFotoLugar)) {
        Storage::disk('public')->delete($oldFotoLugar);
    }
    if ($newFotoSituacionPath && !empty($oldFotoSituacion) && Storage::disk('public')->exists($oldFotoSituacion)) {
        Storage::disk('public')->delete($oldFotoSituacion);
    }

    $hecho = $hecho->fresh()->load(['vehiculos.conductores', 'lesionados']);

    return response()->json([
        'message' => 'Hecho actualizado exitosamente',
        'data'    => $this->withFotoUrls($hecho),
    ], 200);
}

    public function subirDescargo(Request $request, Hechos $hecho)
    {
        $validator = Validator::make($request->all(), [
            'descargo' => 'required|file|mimes:pdf,jpeg,png|max:5120',
        ], $this->messages());

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $path = $request->file('descargo')->store('descargos', 'public');

        $hecho->descargo_path = $path;
        $hecho->save();

        return response()->json([
            'message' => 'Descargo subido correctamente',
            'path'    => Storage::url($path),
        ], 200);
    }

    private function withFotoUrls(Hechos $hecho): array
    {
        $data = $hecho->toArray();

        $data['foto_lugar_url']     = $this->publicStoragePath($hecho->foto_lugar);
        $data['foto_situacion_url'] = $this->publicStoragePath($hecho->foto_situacion);

        return $data;
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
            'errors'  => $errors,
        ], 422);
    }

    private function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio.',
            'string'   => 'Escribe un texto válido.',
            'max'      => 'Máximo :max caracteres.',
            'min'      => 'El valor mínimo es :min.',
            'date'     => 'Escribe una fecha válida.',
            'date_format' => 'La hora debe tener formato HH:MM (ej. 08:30).',
            'integer'  => 'Solo se permiten números (sin letras).',
            'numeric'  => 'Solo se permiten números.',
            'boolean'  => 'Valor inválido.',
            'unique'   => 'Ese valor ya existe, usa uno diferente.',
            'image'    => 'El archivo debe ser una imagen.',
            'mimes'    => 'Formato no permitido. Usa: :values.',
            'file'     => 'Archivo inválido.',
            'between'  => 'El valor está fuera de rango.',

            'folio_c5i.required' => 'Falta el folio C5i.',
            'folio_c5i.unique'   => 'Ese folio C5i ya está registrado.',
            'perito.required'    => 'Falta el nombre del perito.',
            'unidad.required'    => 'Falta la unidad.',
            'sector.in'          => 'Selecciona un sector válido.',
            'tiempo.in'          => 'Selecciona un tiempo válido (DÍA, NOCHE, AMANECER o ATARDECER).',
            'clima.in'           => 'Selecciona un clima válido.',
            'condiciones.in'     => 'Selecciona condiciones válidas.',
            'situacion.in'       => 'Selecciona una situación válida.',

            'vehiculos_mp.required' => 'Indica cuántos vehículos se turnaron (puede ser 0).',
            'vehiculos_mp.integer'  => 'En “Vehículos MP” solo se permiten números.',
            'personas_mp.required'  => 'Indica cuántas personas se turnaron (puede ser 0).',
            'personas_mp.integer'   => 'En “Personas MP” solo se permiten números.',

            'oficio_mp.required_if' => 'Si la situación es TURNADO, debes capturar el oficio del MP.',

            'lat.between'   => 'Latitud inválida.',
            'lng.between'   => 'Longitud inválida.',

            'monto_danos_patrimoniales.numeric' => 'En “Monto daños patrimoniales” solo se permiten números.',
            'monto_danos_patrimoniales.min'     => 'El monto no puede ser negativo.',
            'propiedades_afectadas.max'         => 'Máximo 2000 caracteres en “Propiedades afectadas”.',

            'foto_lugar.max'        => 'La foto del lugar es muy pesada (máximo 5 MB).',
            'foto_situacion.max'    => 'La foto de situación es muy pesada (máximo 5 MB).',
        ];
    }

    private function normalizeCatalogFields(Request $request): void
    {
        $map = [];

        if ($request->has('sector')) {
            $map['sector'] = strtoupper($this->removeAccents((string)$request->input('sector')));
        }
        if ($request->has('tiempo')) {
            $map['tiempo'] = strtoupper($this->removeAccents((string)$request->input('tiempo')));
            if ($map['tiempo'] === 'ATARDECER' || $map['tiempo'] === 'ATARDECER ') {
                $map['tiempo'] = 'ATARDECER';
            }
        }
        if ($request->has('clima')) {
            $map['clima'] = strtoupper($this->removeAccents((string)$request->input('clima')));
        }
        if ($request->has('condiciones')) {
            $map['condiciones'] = strtoupper($this->removeAccents((string)$request->input('condiciones')));
        }
        if ($request->has('situacion')) {
            $map['situacion'] = strtoupper($this->removeAccents((string)$request->input('situacion')));
        }

        if (!empty($map)) {
            $request->merge($map);
        }
    }

    private function removeAccents(string $string): string
    {
        $unwanted_array = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'Â'=>'A','Ê'=>'E','Î'=>'I','Ô'=>'O','Û'=>'U',
            'Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O','Ü'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U',
            'à'=>'A','è'=>'E','ì'=>'I','ò'=>'O','ù'=>'U',
            'â'=>'A','ê'=>'E','î'=>'I','ô'=>'O','û'=>'U',
            'ä'=>'A','ë'=>'A','ï'=>'I','ö'=>'O','ü'=>'U',
            'Ñ'=>'N','ñ'=>'N','Ç'=>'C','ç'=>'C'
        ];

        return strtr($string, $unwanted_array);
    }

    private function publicStoragePath(?string $storedPath): ?string
    {
        if (empty($storedPath)) return null;

        $u = Storage::disk('public')->url($storedPath);

        $p = parse_url($u);
        if (is_array($p) && !empty($p['path'])) {
            $out = $p['path'];
            if (!empty($p['query'])) $out .= '?' . $p['query'];
            return $out;
        }

        return $u;
    }

    private function applyHechosVisibilityScope($query, $usuario): void
    {
        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        $UNIDAD_CARRETERAS_ID = 4;

        $query->where(function ($q) use ($usuario, $unidadId, $delegacionId, $UNIDAD_CARRETERAS_ID) {
            $q->where('created_by', $usuario->id);

            if ($UNIDAD_CARRETERAS_ID > 0 && $unidadId === $UNIDAD_CARRETERAS_ID) {
                $q->orWhere('unidad_org_id', $UNIDAD_CARRETERAS_ID);
                return;
            }

            if ($unidadId === 2) {
                if ($delegacionId <= 0) {
                    return;
                }

                $esRegional = \App\Models\Delegacion::query()
                    ->where('id', $delegacionId)
                    ->whereNull('delegacion_padre_id')
                    ->exists();

                if ($usuario->hasRole('Subdirector')) {
                    if ($esRegional) {
                        $ids = \App\Models\Delegacion::query()
                            ->where('id', $delegacionId)
                            ->orWhere('delegacion_padre_id', $delegacionId)
                            ->pluck('id')
                            ->toArray();

                        $q->orWhereIn('delegacion_id', $ids);
                    } else {
                        $q->orWhere('delegacion_id', $delegacionId);
                    }
                } else {
                    $q->orWhere('delegacion_id', $delegacionId);
                }

                return;
            }

            if ($unidadId > 0) {
                $q->orWhere('unidad_org_id', $unidadId);
                return;
            }
        });
    }

    private function userCanEditHecho($usuario, \App\Models\Hechos $hecho): bool
    {
        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector')
        ) {
            $q = \App\Models\Hechos::query()->whereKey($hecho->id);
            $this->applyHechosVisibilityScope($q, $usuario);
            return $q->exists();
        }

        if ((int) $usuario->id === (int) ($hecho->created_by ?? 0)) {
            $q = \App\Models\Hechos::query()->whereKey($hecho->id);
            $this->applyHechosVisibilityScope($q, $usuario);
            return $q->exists();
        }

        return false;
    }

    public function whatsappLink(\App\Models\Hechos $hecho)
    {
        $url = WhatsAppLink::linkForHecho($hecho);

        return response()->json([
            'ok' => true,
            'wa_url' => $url,
        ]);
    }

    public function sendWhatsapp(Request $request, Hechos $hecho)
    {
        $user = $request->user();

        if (!$user || !$user->can('ver hechos')) {
            return response()->json([
                'ok' => false,
                'message' => 'No autorizado.',
            ], 403);
        }

        if (!is_null($hecho->whatsapp_sent_at)) {
            return response()->json([
                'ok' => true,
                'message' => 'Este hecho ya fue compartido por WhatsApp.',
            ], 200);
        }

        $hecho->load(['vehiculos']);

        $baseText = WhatsAppLink::textForHecho($hecho);

        $lat = null;
        $lng = null;

        if (is_numeric($hecho->lat) && is_numeric($hecho->lng)) {
            $lat = (float) $hecho->lat;
            $lng = (float) $hecho->lng;
        } else {
            $c5Text = null;

            // Si tienes el texto crudo del C5i guardado en algún campo, ponlo aquí:
            // $c5Text = (string) ($hecho->reporte_c5i_texto ?? '');

            if (is_string($c5Text) && trim($c5Text) !== '') {
                $parsed = \App\Services\WhatsApp\C5IReport::parseCoordsFromC5IText($c5Text);
                if ($parsed && isset($parsed['lat'], $parsed['lng'])) {
                    $lat = (float) $parsed['lat'];
                    $lng = (float) $parsed['lng'];
                }
            }
        }

        $recoText = "RECOMENDACIÓN: NO DISPONIBLE (SIN COORDENADAS).";
        $gmaps = null;

        if ($lat !== null && $lng !== null) {
            $r = \App\Services\WhatsApp\NearestUnit::recommendForCoords($lat, $lng, 3);
            $recoText = \App\Services\WhatsApp\NearestUnit::recommendationText($r);
            $gmaps = \App\Services\WhatsApp\C5IReport::googleMapsLinkFromCoords($lat, $lng);
        }

        $message = trim($baseText . "\n\n" . $recoText . ($gmaps ? "\n" . $gmaps : ''));

        $media = [];

        if (!empty($hecho->foto_lugar)) {
            $media[] = asset('storage/' . ltrim($hecho->foto_lugar, '/'));
        }

        if (!empty($hecho->foto_situacion)) {
            $media[] = asset('storage/' . ltrim($hecho->foto_situacion, '/'));
        }

        foreach ($hecho->vehiculos as $v) {
            if (!empty($v->fotos)) {
                $media[] = asset('storage/' . ltrim($v->fotos, '/'));
            }
        }

        $chatId = (string) env('WHATSAPP_DEFAULT_CHAT_ID');
        if ($chatId === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Falta configurar WHATSAPP_DEFAULT_CHAT_ID.',
            ], 500);
        }

        $resp = WhatsAppBot::sendToChat($chatId, $message, $media);

        if (!($resp['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo enviar a WhatsApp.',
                'data' => $resp,
            ], 500);
        }

        $hecho->whatsapp_sent_at    = now();
        $hecho->whatsapp_chat_id    = $chatId;
        $hecho->whatsapp_message_id = (string) ($resp['id'] ?? '');
        $hecho->save();

        return response()->json([
            'ok' => true,
            'message' => 'Hecho compartido por WhatsApp.',
            'data' => [
                'hecho_id' => $hecho->id,
                'whatsapp_message_id' => (string) ($resp['id'] ?? ''),
            ],
        ], 200);
    }
}
