<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

        $query = Hechos::with(['vehiculos.conductores', 'lesionados']);

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->query('fecha'));
        }

        $hechos = $query->orderByDesc('id')->paginate($perPage);

        return response()->json($hechos);
    }

    public function buscar(Request $request)
    {
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
            ])
            ->where(function ($w) use ($q, $like) {
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
            'folio_c5i'             => 'required|string|max:20|unique:hechos,folio_c5i',
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
            'vehiculos_mp'          => 'required|integer|min:0',
            'personas_mp'           => 'required|integer|min:0',

            'danos_patrimoniales'        => 'nullable|boolean',
            'propiedades_afectadas'      => 'nullable|string|max:2000',
            'monto_danos_patrimoniales'  => 'nullable|numeric|min:0',

            'lat'                   => 'nullable|numeric|between:-90,90',
            'lng'                   => 'nullable|numeric|between:-180,180',
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

        $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');
        $validated['danos_patrimoniales']   = $request->boolean('danos_patrimoniales');

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $hasCoords = $request->filled('lat') && $request->filled('lng');
        if ($hasCoords && empty($validated['fuente_ubicacion'])) {
            $validated['fuente_ubicacion'] = 'GPS_APP';
        }

        if (!$request->filled('lat')) $validated['lat'] = null;
        if (!$request->filled('lng')) $validated['lng'] = null;

        if (!$request->has('danos_patrimoniales')) $validated['danos_patrimoniales'] = 0;
        if ($request->has('monto_danos_patrimoniales') && !$request->filled('monto_danos_patrimoniales')) {
            $validated['monto_danos_patrimoniales'] = null;
        }
        if ($request->has('propiedades_afectadas') && trim((string)$request->input('propiedades_afectadas')) === '') {
            $validated['propiedades_afectadas'] = null;
        }

        $validated['created_by'] = $user->id;

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

        $hecho->load(['vehiculos.conductores', 'lesionados']);

        return response()->json([
            'message' => 'Hecho creado exitosamente',
            'data'    => $this->withFotoUrls($hecho),
        ], 201);
    }

    public function show(Hechos $hecho)
    {
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
            'vehiculos_mp'          => 'sometimes|required|integer|min:0',
            'personas_mp'           => 'sometimes|required|integer|min:0',

            'danos_patrimoniales'        => 'sometimes|nullable|boolean',
            'propiedades_afectadas'      => 'sometimes|nullable|string|max:2000',
            'monto_danos_patrimoniales'  => 'sometimes|nullable|numeric|min:0',

            'lat'                   => 'sometimes|nullable|numeric|between:-90,90',
            'lng'                   => 'sometimes|nullable|numeric|between:-180,180',
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

        if ($request->hasFile('foto_lugar')) {
            if (!empty($hecho->foto_lugar) && Storage::disk('public')->exists($hecho->foto_lugar)) {
                Storage::disk('public')->delete($hecho->foto_lugar);
            }
            $validated['foto_lugar'] = $request->file('foto_lugar')->store("hechos/{$hecho->id}", 'public');
        }

        if ($request->hasFile('foto_situacion')) {
            if (!empty($hecho->foto_situacion) && Storage::disk('public')->exists($hecho->foto_situacion)) {
                Storage::disk('public')->delete($hecho->foto_situacion);
            }
            $validated['foto_situacion'] = $request->file('foto_situacion')->store("hechos/{$hecho->id}", 'public');
        }

        $hecho->update($validated);

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
}
