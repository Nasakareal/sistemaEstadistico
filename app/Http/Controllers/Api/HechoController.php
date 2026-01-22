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
     * GET /api/hechos/buscar?q=ABC&per_page=20
     * Busca por folio del hecho, calle/colonia/municipio, placa/serie del vehículo y nombre del conductor.
     * Recomendación: coloca la ruta /hechos/buscar ANTES de /hechos/{hecho}.
     */
    public function buscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int)($request->query('per_page', 20));
        $perPage = $perPage > 0 ? min($perPage, 50) : 20;

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([
                'q'    => $q,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
            ]);
        }

        // Normaliza el término igual que guardas (MAYÚSCULAS SIN ACENTOS)
        $qNorm = strtoupper($this->removeAccents($q));
        $like  = '%' . str_replace(['%', '_'], ['\%', '\_'], $qNorm) . '%';

        $results = Hechos::query()
            ->with(['vehiculos.conductores'])
            ->where(function ($w) use ($like) {
                // Campos del hecho
                $w->orWhere('folio_c5i', 'like', $like)
                  ->orWhere('perito', 'like', $like)
                  ->orWhere('unidad', 'like', $like)
                  ->orWhere('sector', 'like', $like)
                  ->orWhere('calle', 'like', $like)
                  ->orWhere('colonia', 'like', $like)
                  ->orWhere('municipio', 'like', $like)
                  ->orWhere('tipo_hecho', 'like', $like)
                  ->orWhere('causas', 'like', $like)
                  ->orWhere('colision_camino', 'like', $like);

                // Vehículos y conductores
                $w->orWhereHas('vehiculos', function ($v) use ($like) {
                    $v->where(function ($vv) use ($like) {
                        // Ajusta aquí si tu campo se llama "placas" en vez de "placa"
                        $vv->orWhere('placa', 'like', $like)
                           ->orWhere('serie', 'like', $like);
                    });

                    $v->orWhereHas('conductores', function ($c) use ($like) {
                        // Ajusta a tus columnas reales (estos son nombres comunes)
                        $c->where(function ($cc) use ($like) {
                            $cc->orWhere('nombre', 'like', $like)
                               ->orWhere('apellido_paterno', 'like', $like)
                               ->orWhere('apellido_materno', 'like', $like)
                               ->orWhere('nombre_completo', 'like', $like);
                        });
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'q'    => $qNorm,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
                'last_page'    => $results->lastPage(),
            ],
        ]);
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
            'data'    => $hecho->load(['vehiculos.conductores', 'lesionados'])
        ], 201);
    }

    public function show(Hechos $hecho)
    {
        $hecho->load(['vehiculos.conductores', 'lesionados']);

        return response()->json([
            'data' => $hecho
        ]);
    }

    public function update(Request $request, Hechos $hecho)
    {
        $user = $request->user();

        $validated = $request->validate([
            'folio_c5i' => [
                'required','string','max:20',
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
            'data'    => $hecho->fresh()->load(['vehiculos.conductores', 'lesionados'])
        ]);
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
            'path'    => Storage::url($path)
        ]);
    }

    public function destroy(Hechos $hecho)
    {
        $hecho->delete();

        return response()->json([
            'message' => 'Hecho eliminado'
        ]);
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
