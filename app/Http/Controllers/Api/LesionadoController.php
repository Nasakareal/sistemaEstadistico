<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use App\Models\Lesionado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LesionadoController extends Controller
{
    public function index(Hechos $hecho)
    {
        return response()->json([
            'data' => $hecho->lesionados()->orderByDesc('id')->get()
        ]);
    }

    public function store(Request $request, Hechos $hecho)
    {
        $validated = $request->validate($this->rules());

        $validated = $this->normalize($validated);

        $lesionado = $hecho->lesionados()->create($validated);

        return response()->json([
            'message' => 'Lesionado creado',
            'data'    => $lesionado
        ], 201);
    }

    public function show(Hechos $hecho, Lesionado $lesionado)
    {
        if (!$hecho->lesionados()->where('lesionados.id', $lesionado->id)->exists()) {
            abort(404);
        }

        return response()->json([
            'data' => $lesionado
        ]);
    }

    public function update(Request $request, Hechos $hecho, Lesionado $lesionado)
    {
        if (!$hecho->lesionados()->where('lesionados.id', $lesionado->id)->exists()) {
            abort(404);
        }

        $validated = $request->validate($this->rules($lesionado->id));

        $validated = $this->normalize($validated);

        $lesionado->update($validated);

        return response()->json([
            'message' => 'Lesionado actualizado',
            'data'    => $lesionado->fresh()
        ]);
    }

    public function destroy(Hechos $hecho, Lesionado $lesionado)
    {
        if (!$hecho->lesionados()->where('lesionados.id', $lesionado->id)->exists()) {
            abort(404);
        }

        $lesionado->delete();

        return response()->json([
            'message' => 'Lesionado eliminado'
        ]);
    }

    /* ===================== HELPERS ===================== */

    private function rules(?int $ignoreId = null): array
    {
        // Ajusta estos campos a tu tabla real si cambian nombres.
        // Si tienes campos extra en lesionados (ej. hospital, traslado, etc.), me los pegas y lo amplío.

        $uniqueCurp = Rule::unique('lesionados', 'curp');
        if ($ignoreId) $uniqueCurp->ignore($ignoreId);

        return [
            'nombre'            => 'required|string|max:255',
            'edad'              => 'nullable|integer|min:0|max:120',
            'sexo'              => 'nullable|string|in:MASCULINO,FEMENINO,OTRO',
            'domicilio'         => 'nullable|string|max:255',
            'telefono'          => 'nullable|digits:10',
            'curp'              => ['nullable','string','max:18',$uniqueCurp],

            'tipo_lesion'       => 'nullable|string|max:255',
            'gravedad'          => 'nullable|string|max:50',
            'traslado'          => 'nullable|boolean',
            'hospital'          => 'nullable|string|max:255',
            'observaciones'     => 'nullable|string',

            // Si tu tabla tiene campo para "responsable" o "acompañante", lo agregamos.
        ];
    }

    private function normalize(array $data): array
    {
        $upper = [
            'nombre','sexo','domicilio','curp',
            'tipo_lesion','gravedad','hospital','observaciones'
        ];

        foreach ($upper as $k) {
            if (array_key_exists($k, $data) && is_string($data[$k])) {
                $data[$k] = strtoupper($this->removeAccents($data[$k]));
            }
        }

        // booleans correctos para JSON
        if (array_key_exists('traslado', $data)) {
            $data['traslado'] = (bool)$data['traslado'];
        }

        return $data;
    }

    private function removeAccents(string $s): string
    {
        return strtr($s, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'à'=>'A','è'=>'E','ì'=>'I','ò'=>'O','ù'=>'U',
            'Ñ'=>'N','ñ'=>'N','Ç'=>'C','ç'=>'C'
        ]);
    }
}
