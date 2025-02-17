<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HechosController extends Controller
{
    public function index()
    {
        $hechos = Hechos::all();

        return view('hechos.index', compact('hechos'));
    }

    public function create()
    {
        return view('hechos.create');
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();

        $validated = $request->validate([
            'folio_c5i' => 'required|string|max:20|unique:hechos,folio_c5i',
            'perito' => 'required|string|max:255',
            'autorizacion_practico' => 'required|string|max:255',
            'unidad' => 'required|string|max:50',
            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
            'sector' => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'entre_calles' => 'nullable|string|max:255',
            'municipio' => 'required|string|max:100',
            'tipo_hecho' => 'required|string|max:255',
            'superficie_via' => 'required|string|max:50',
            'tiempo' => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima' => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones' => 'required|string|in:Bueno,Regular,Malo',
            'control_transito' => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas' => 'required|string|max:255',
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
        ]);

        // Se marca como verdadero si se envía la opción
        $validated['checaron_antecedentes'] = $request->has('checaron_antecedentes');

        // Recorrer y transformar cada valor de tipo string: quitar acentos y pasar a mayúsculas
        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['created_by'] = $usuario->id;

        $hecho = Hechos::create($validated);

        // Redirigir al formulario de edición del hecho recien creado
        return redirect()->route('hechos.edit', $hecho->id)->with('success', 'Hecho creado exitosamente.');
    }

    public function show(Hechos $hecho)
    {
        $hecho->load('vehiculos');
        return view('hechos.show', compact('hecho'));
    }

    public function edit(Hechos $hecho)
    {
        $usuario = auth()->user();

        if ($usuario->id !== $hecho->created_by && !$usuario->hasRole('Administrador')) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para editar este hecho.');
        }

        return view('hechos.edit', compact('hecho'));
    }

    public function update(Request $request, Hechos $hecho)
    {
        $usuario = auth()->user();

        if ($usuario->id !== $hecho->created_by && !$usuario->hasRole('Administrador')) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para modificar este hecho.');
        }

        $validated = $request->validate([
            'folio_c5i' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hechos')->ignore($hecho->id),
            ],
            'hora' => 'required|date_format:H:i',
            'fecha' => 'required|date',
            'sector' => 'required|string|in:REVOLUCIÓN,NUEVA ESPAÑA,INDEPENDENCIA,REPÚBLICA,CENTRO',
            'calle' => 'required|string|max:255',
            'colonia' => 'required|string|max:255',
            'entre_calles' => 'nullable|string|max:255',
            'municipio' => 'required|string|max:100',
            'tipo_hecho' => 'required|string|max:255',
            'superficie_via' => 'required|string|max:50',
            'tiempo' => 'required|string|in:Día,Noche,Amanecer,Atardecer',
            'clima' => 'required|string|in:Bueno,Malo,Nublado,Lluvioso',
            'condiciones' => 'required|string|in:Bueno,Regular,Malo',
            'control_transito' => 'required|string|max:50',
            'checaron_antecedentes' => 'nullable|boolean',
            'causas' => 'required|string|max:255',
            'colision_camino' => 'required|string|max:255',
            'situacion' => 'required|string|in:RESUELTO,PENDIENTE,TURNADO,REPORTE',
            'oficio_mp' => 'nullable|string|max:255|required_if:situacion,TURNADO',
            'vehiculos_mp' => 'required|integer|min:0',
            'personas_mp' => 'required|integer|min:0',
        ]);

        // Transformar los valores de tipo string: quitar acentos y pasar a mayúsculas
        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = strtoupper($this->removeAccents($value));
            }
        }

        $validated['updated_by'] = $usuario->id;

        $hecho->update($validated);

        return redirect()->route('hechos.index')->with('success', 'Hecho actualizado exitosamente.');
    }

    public function destroy(Hechos $hecho)
    {
        $usuario = auth()->user();

        if (!$usuario->hasRole('Administrador')) {
            return redirect()->route('hechos.index')->with('error', 'No tienes permiso para eliminar este hecho.');
        }

        $hecho->delete();

        return redirect()->route('hechos.index')->with('success', 'Hecho eliminado exitosamente.');
    }
    
    private function removeAccents($string)
    {
        $unwanted_array = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'à' => 'A', 'è' => 'E', 'ì' => 'I', 'ò' => 'O', 'ù' => 'U',
            'â' => 'A', 'ê' => 'E', 'î' => 'I', 'ô' => 'O', 'û' => 'U',
            'ä' => 'A', 'ë' => 'E', 'ï' => 'I', 'ö' => 'O', 'ü' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
            'Ç' => 'C', 'ç' => 'C'
        ];
        return strtr($string, $unwanted_array);
    }
}
