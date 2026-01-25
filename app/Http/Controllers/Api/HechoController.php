<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class HechoController extends Controller
{
    private const SECTORES = ['REVOLUCION','NUEVA ESPANA','INDEPENDENCIA','REPUBLICA','CENTRO'];
    private const TIEMPOS  = ['DIA','NOCHE','AMANECER','ATARDecer'];
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
                'hechos.folio_c5i',
                'hechos.fecha',
                'hechos.calle',
                'hechos.colonia',
                'hechos.municipio',
                'hechos.situacion',
                'hechos.foto_lugar',
                'hechos.foto_situacion',
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

        // ✅ Normaliza valores tipo catálogo ANTES de validar (acepta con acento o sin acento)
        $this->normalizeCatalogFields($request);

        $validated = $request->validate([
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
            'tiempo'                => ['required','string', Rule::in(['DIA','NOCHE','AMANECER','ATARDecer','ATARDECER'])],
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
            'foto_lugar'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Si situacion exige foto_situacion en creación, sí la pedimos aquí
        $situacion = (string)($validated['situacion'] ?? '');
        if (in_array($situacion, ['RESUELTO', 'TURNADO'], true)) {
            $request->validate([
                'foto_situacion' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
        }

        $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');

        // Normaliza strings (sin acento, mayúsculas) para guardar consistente
        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
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

        // ✅ Normaliza catálogos ANTES de validar
        $this->normalizeCatalogFields($request);

        // ✅ Update parcial: permite subir fotos sin mandar todo
        $validated = $request->validate([
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
            'tiempo'                => ['sometimes','required','string', Rule::in(['DIA','NOCHE','AMANECER','ATARDecer','ATARDECER'])],
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
            'foto_lugar'            => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_situacion'        => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // ✅ Regla de foto_situacion: SOLO si situacion lo exige Y no hay foto previa Y no viene archivo nuevo
        $situacionNueva = $request->has('situacion')
            ? strtoupper($this->removeAccents((string)$request->input('situacion')))
            : null;

        $situacionEfectiva = $situacionNueva ?? strtoupper((string)($hecho->situacion ?? ''));

        if (in_array($situacionEfectiva, ['RESUELTO', 'TURNADO'], true)) {
            $yaTieneFoto = !empty($hecho->foto_situacion);
            $vieneArchivo = $request->hasFile('foto_situacion');

            if (!$yaTieneFoto && !$vieneArchivo) {
                $request->validate([
                    'foto_situacion' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
                ]);
            }
        }

        // boolean
        if ($request->has('checaron_antecedentes')) {
            $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');
        }

        // Normaliza strings para guardar consistente
        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['updated_by'] = $user->id;

        // Archivos
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

        // ✅ Si solo venían fotos, esto no truena porque validated puede traer solo foto_* y updated_by
        $hecho->update($validated);

        $hecho = $hecho->fresh()->load(['vehiculos.conductores', 'lesionados']);

        return response()->json([
            'message' => 'Hecho actualizado exitosamente',
            'data'    => $this->withFotoUrls($hecho),
        ], 200);
    }

    public function subirDescargo(Request $request, Hechos $hecho)
    {
        $request->validate([
            'descargo' => 'required|file|mimes:pdf,jpeg,png|max:5120',
        ]);

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

        // OJO: regresamos RUTA RELATIVA /storage/... (no absoluta)
        $data['foto_lugar_url']     = $this->publicStoragePath($hecho->foto_lugar);
        $data['foto_situacion_url'] = $this->publicStoragePath($hecho->foto_situacion);

        return $data;
    }


    /**
     * Normaliza campos de catálogo en el Request para que el validator (Rule::in)
     * funcione aunque Flutter/web manden con acentos o minúsculas.
     */
    private function normalizeCatalogFields(Request $request): void
    {
        $map = [];

        if ($request->has('sector')) {
            $map['sector'] = strtoupper($this->removeAccents((string)$request->input('sector')));
        }
        if ($request->has('tiempo')) {
            $map['tiempo'] = strtoupper($this->removeAccents((string)$request->input('tiempo')));
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
            'ä'=>'A','ë'=>'E','ï'=>'I','ö'=>'O','ü'=>'U',
            'Ñ'=>'N','ñ'=>'N','Ç'=>'C','ç'=>'C'
        ];

        return strtr($string, $unwanted_array);
    }

    private function publicStoragePath(?string $storedPath): ?string
    {
        if (empty($storedPath)) return null;

        // Genera URL según config (puede salir con host malo)
        $u = Storage::disk('public')->url($storedPath);

        // Fuerza a "solo path": /storage/...
        $p = parse_url($u);
        if (is_array($p) && !empty($p['path'])) {
            $out = $p['path'];
            if (!empty($p['query'])) $out .= '?' . $p['query'];
            return $out;
        }

        // Fallback
        return $u;
    }

}
