<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class HechoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int)($request->query('per_page', 20));
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $hechos = Hechos::with(['vehiculos.conductores', 'lesionados'])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($hechos);
    }

    /**
     * GET /api/hechos/buscar?q=ABC&per_page=20&page=1
     * ✅ SOLO busca en:
     * - hechos.id
     * - hechos.folio_c5i
     * - hechos.calle
     * - hechos.colonia
     * - vehiculos.placas
     * - vehiculos.serie
     * - conductores.nombre
     * - lesionados.nombre
     *
     * Recomendación: coloca la ruta /hechos/buscar ANTES de /hechos/{hecho}.
     */
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

        // Normaliza (mayúsculas sin acentos) y escapa LIKE
        $qNorm = strtoupper($this->removeAccents($q));
        $like  = '%' . addcslashes($qNorm, "%_\\") . '%';

        $query = Hechos::query()
            // ✅ Regresa solo lo necesario para la lista (evita “campos que no queremos”)
            ->select([
                'hechos.id',
                'hechos.folio_c5i',
                'hechos.fecha',
                'hechos.calle',
                'hechos.colonia',
                'hechos.municipio',
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

                // 1) ID exacto si el usuario escribió número
                if (ctype_digit($q)) {
                    $w->orWhere('hechos.id', (int)$q);
                }

                // 2) Hechos: folio, calle, colonia
                $w->orWhere('hechos.folio_c5i', 'like', $like)
                  ->orWhere('hechos.calle', 'like', $like)
                  ->orWhere('hechos.colonia', 'like', $like);

                // 3) Vehículos: placas / serie
                $w->orWhereHas('vehiculos', function ($v) use ($like) {
                    $v->where(function ($vv) use ($like) {
                        $vv->where('vehiculos.placas', 'like', $like)
                           ->orWhere('vehiculos.serie', 'like', $like);
                    });
                });

                // 4) Conductores: nombre (vía vehículos)
                $w->orWhereHas('vehiculos.conductores', function ($c) use ($like) {
                    $c->where('conductores.nombre', 'like', $like);
                });

                // 5) Lesionados: nombre
                $w->orWhereHas('lesionados', function ($l) use ($like) {
                    $l->where('lesionados.nombre', 'like', $like);
                });
            })
            ->orderByDesc('hechos.id');

        $results = $query->paginate($perPage);

        return response()->json([
            'data' => $results->items(),
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

        $validated = $request->validate([
            'folio_c5i'             => 'required|string|max:20|unique:hechos,folio_c5i',
            'perito'                => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad'                => 'required|string|max:50',
            'hora'                  => 'required|date_format:H:i',
            'fecha'                 => 'required|date',
            'sector'                => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
            'calle'                 => 'required|string|max:255',
            'colonia'               => 'required|string|max:255',
            'entre_calles'          => 'nullable|string|max:255',
            'municipio'             => 'required|string|max:100',
            'tipo_hecho'            => 'required|string|max:255',
            'superficie_via'        => 'required|string|max:50',
            'tiempo'                => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima'                 => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones'           => 'required|string|in:Bueno,Regular,Malo',
            'control_transito'      => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas'                => 'required|string|max:255',
            'colision_camino'       => 'required|string|max:255',
            'situacion'             => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp'             => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp'          => 'required|integer|min:0',
            'personas_mp'           => 'required|integer|min:0',
        ]);

        $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['created_by'] = $user->id;

        $hecho = Hechos::create($validated);

        return response()->json([
            'message' => 'Hecho creado exitosamente',
            'data'    => $hecho->load(['vehiculos.conductores', 'lesionados']),
        ], 201);
    }

    public function show(Hechos $hecho)
    {
        $hecho->load(['vehiculos.conductores', 'lesionados']);

        return response()->json([
            'data' => $hecho,
        ], 200);
    }

    public function update(Request $request, Hechos $hecho)
    {
        $user = $request->user();

        $validated = $request->validate([
            'folio_c5i' => [
                'required', 'string', 'max:20',
                Rule::unique('hechos', 'folio_c5i')->ignore($hecho->id),
            ],
            'perito'                => 'required|string|max:255',
            'autorizacion_practico' => 'nullable|string|max:255',
            'unidad'                => 'required|string|max:50',
            'hora'                  => 'required|date_format:H:i',
            'fecha'                 => 'required|date',
            'sector'                => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
            'calle'                 => 'required|string|max:255',
            'colonia'               => 'required|string|max:255',
            'entre_calles'          => 'nullable|string|max:255',
            'municipio'             => 'required|string|max:100',
            'tipo_hecho'            => 'required|string|max:255',
            'superficie_via'        => 'required|string|max:50',
            'tiempo'                => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima'                 => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones'           => 'required|string|in:Bueno,Regular,Malo',
            'control_transito'      => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas'                => 'required|string|max:255',
            'colision_camino'       => 'required|string|max:255',
            'situacion'             => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp'             => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp'          => 'required|integer|min:0',
            'personas_mp'           => 'required|integer|min:0',
        ]);

        $validated['checaron_antecedentes'] = $request->boolean('checaron_antecedentes');

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['updated_by'] = $user->id;

        $hecho->update($validated);

        return response()->json([
            'message' => 'Hecho actualizado exitosamente',
            'data'    => $hecho->fresh()->load(['vehiculos.conductores', 'lesionados']),
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

    public function destroy(Hechos $hecho)
    {
        $hecho->delete();

        return response()->json([
            'message' => 'Hecho eliminado',
        ], 200);
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
}
