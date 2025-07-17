<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class HechoController extends Controller
{
    /**
     * Listar todos los hechos
     */
    public function index(Request $request)
    {
        $hechos = Hechos::with('vehiculos')->get();
        return response()->json([
            'data' => $hechos
        ]);
    }

    /**
     * Crear un nuevo hecho
     */
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

        // Forzar booleano en antecedentes
        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');

        // Quitar acentos y pasar a mayúsculas
        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['created_by'] = $user->id;
        $hecho = Hechos::create($validated);

        return response()->json([
            'message' => 'Hecho creado exitosamente',
            'data'    => $hecho
        ], 201);
    }

    /**
     * Ver detalle de un hecho
     */
    public function show($id)
    {
        $hecho = Hechos::with('vehiculos')->findOrFail($id);
        return response()->json([
            'data' => $hecho
        ]);
    }

    /**
     * Actualizar un hecho existente
     */
    public function update(Request $request, $id)
    {
        $user  = $request->user();
        $hecho = Hechos::findOrFail($id);

        $validated = $request->validate([
            'folio_c5i'             => [
                'required','string','max:20',
                Rule::unique('hechos')->ignore($hecho->id, 'id'),
            ],
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

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['updated_by'] = $user->id;
        $hecho->update($validated);

        return response()->json([
            'message' => 'Hecho actualizado exitosamente',
            'data'    => $hecho
        ]);
    }

    /**
     * Subir un descargo (PDF o imagen) para un hecho
     */
    public function subirDescargo(Request $request, $id)
    {
        $request->validate([
            'descargo' => 'required|file|mimes:pdf,jpeg,png|max:5120',
        ]);

        $hecho = Hechos::findOrFail($id);

        // Guardar el archivo en storage/app/public/descargos
        $path = $request->file('descargo')->store('descargos', 'public');

        // Suponiendo que tu tabla hechos tiene columna 'descargo_path'
        $hecho->descargo_path = $path;
        $hecho->save();

        return response()->json([
            'message' => 'Descargo subido correctamente',
            'path'    => Storage::url($path)
        ]);
    }

    /**
     * Eliminar un hecho
     */
    public function destroy($id)
    {
        $hecho = Hechos::findOrFail($id);
        $hecho->delete();

        return response()->json([
            'message' => 'Hecho eliminado'
        ]);
    }

    /**
     * Función auxiliar para quitar acentos
     */
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
